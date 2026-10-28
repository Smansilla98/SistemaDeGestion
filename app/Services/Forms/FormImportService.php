<?php

declare(strict_types=1);

namespace App\Services\Forms;

use App\Models\ActivityCategory;
use App\Models\AgendaNotice;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\FormSource;
use App\Models\FormSourceRow;
use App\Models\Workshop;
use App\Models\WorkshopEnrollment;
use App\Services\Community\OrganizationDirectory;
use App\Services\Community\WorkshopService;
use App\Services\Notifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Lee las respuestas de un formulario (planilla de Google Sheets vinculada al Google Form,
 * o un Excel/CSV descargado) y las vuelca en el centro cultural: alumnos inscriptos,
 * propuestas de talleristas, contactos o actividades de agenda.
 *
 * Cada respuesta se identifica por un hash de su contenido: volver a sincronizar no la repite.
 * Las respuestas con error se vuelven a intentar en la próxima sincronización.
 */
final class FormImportService
{
    private const DISK = 'local';

    /** Palabras de los encabezados de Google Forms para proponer el mapeo. */
    private const GUESS = [
        'last_name' => ['apellido'],
        'name' => ['nombre y apellido', 'nombre completo', 'nombre', 'name'],
        'email' => ['correo', 'email', 'e-mail', 'mail'],
        'phone' => ['whatsapp', 'celular', 'telefono', 'teléfono', 'tel'],
        'document' => ['dni', 'documento'],
        'birth_date' => ['nacimiento'],
        'workshop' => ['taller', 'curso', 'actividad que', 'clase'],
        'category' => ['categoria', 'categoría', 'disciplina', 'rubro'],
        'title' => ['titulo', 'título', 'nombre de la propuesta', 'nombre del taller', 'propuesta', 'actividad'],
        'description' => ['descripcion', 'descripción', 'de que se trata', 'de qué se trata', 'contanos'],
        'date' => ['fecha'],
        'time' => ['hora', 'horario'],
        'consent' => ['novedades', 'difusion', 'difusión', 'acepto', 'autorizo'],
        'notes' => ['comentario', 'observacion', 'observación', 'consulta'],
    ];

    public function __construct(
        private readonly WorkshopService $workshops,
        private readonly Notifier $notifier,
        private readonly OrganizationDirectory $directory,
    ) {}

    // ── Lectura ───────────────────────────────────────────────────────────────

    /**
     * Convierte el link de la planilla en un link de descarga CSV. Sólo acepta Google Sheets
     * (la planilla tiene que estar compartida «cualquier persona con el enlace» o publicada).
     */
    public static function csvUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'docs.google.com') {
            throw ValidationException::withMessages(['sheet_url' => 'Pegá el link de una planilla de Google Sheets (https://docs.google.com/spreadsheets/…).']);
        }
        $path = $parts['path'] ?? '';
        if (str_starts_with($path, '/forms/')) {
            throw ValidationException::withMessages(['sheet_url' => 'Ese es el link del formulario. En Google Forms → Respuestas → «Vincular a Hojas de cálculo», abrí la planilla y pegá su link.']);
        }
        if (preg_match('#^/spreadsheets/d/e/([\w-]+)/pub#', $path, $m)) {
            parse_str($parts['query'] ?? '', $q);
            $q['output'] = 'csv';

            return "https://docs.google.com/spreadsheets/d/e/{$m[1]}/pub?".http_build_query($q);
        }
        if (preg_match('#^/spreadsheets/d/([\w-]+)#', $path, $m)) {
            $gid = null;
            foreach ([$parts['fragment'] ?? '', $parts['query'] ?? ''] as $chunk) {
                if (preg_match('/gid=(\d+)/', $chunk, $g)) {
                    $gid = $g[1];
                }
            }

            return "https://docs.google.com/spreadsheets/d/{$m[1]}/export?format=csv".($gid !== null ? "&gid={$gid}" : '');
        }
        throw ValidationException::withMessages(['sheet_url' => 'No reconozco ese link de Google Sheets.']);
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    public function fetchSheet(string $url): array
    {
        try {
            $response = Http::timeout(25)->withOptions(['allow_redirects' => ['max' => 5]])->get(self::csvUrl($url));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['sheet_url' => 'No se pudo conectar con Google: '.$e->getMessage()]);
        }
        $body = (string) $response->body();
        if (! $response->successful() || str_contains(substr($body, 0, 500), '<html') || str_contains(substr($body, 0, 500), '<!DOCTYPE')) {
            throw ValidationException::withMessages(['sheet_url' => 'Google no devolvió la planilla. Compartila como «Cualquier persona con el enlace: lector» o publicala (Archivo → Compartir → Publicar en la web → CSV).']);
        }

        return self::table(self::parseCsv($body));
    }

    /**
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    public function readFile(string $path, string $extension): array
    {
        $extension = strtolower($extension);
        if (in_array($extension, ['csv', 'txt'], true)) {
            return self::table(self::parseCsv((string) file_get_contents($path)));
        }
        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $raw = $sheet->toArray(null, true, true, false);
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['file' => 'No se pudo leer el archivo: '.$e->getMessage()]);
        }

        return self::table(array_map(fn ($r) => array_map(fn ($v) => $v === null ? '' : (string) $v, (array) $r), $raw));
    }

    /** @return list<list<string>> */
    private static function parseCsv(string $body): array
    {
        $body = preg_replace('/^\x{FEFF}/u', '', $body) ?? $body;
        $first = strtok($body, "\n") ?: '';
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $body);
        rewind($handle);
        $out = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $out[] = array_map(fn ($v) => (string) $v, $row);
        }
        fclose($handle);

        return $out;
    }

    /**
     * @param  list<list<string>>  $raw
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    private static function table(array $raw): array
    {
        $raw = array_values(array_filter($raw, fn ($r) => count(array_filter($r, fn ($v) => trim($v) !== '')) > 0));
        if ($raw === []) {
            return ['headers' => [], 'rows' => []];
        }
        $headers = [];
        foreach (array_shift($raw) as $i => $h) {
            $h = trim($h) !== '' ? trim($h) : 'Columna '.($i + 1);
            $base = $h;
            $n = 2;
            while (in_array($h, $headers, true)) {
                $h = "{$base} ({$n})";
                $n++;
            }
            $headers[] = $h;
        }
        $rows = [];
        foreach ($raw as $r) {
            $row = [];
            foreach ($headers as $i => $h) {
                $row[$h] = trim((string) ($r[$i] ?? ''));
            }
            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    // ── Mapeo ─────────────────────────────────────────────────────────────────

    /**
     * @param  list<string>  $headers
     * @return array<string, string> campo => encabezado
     */
    public static function guessMapping(array $headers, string $target): array
    {
        $map = [];
        $used = [];
        foreach (self::GUESS as $field => $words) {
            if ($target !== 'AGENDA' && in_array($field, ['date', 'time'], true)) {
                continue;
            }
            foreach ($headers as $h) {
                $low = Str::lower(Str::ascii($h));
                if (in_array($h, $used, true) || str_contains($low, 'marca temporal') || $low === 'timestamp'
                    || ($field === 'last_name' && str_contains($low, 'nombre'))) {
                    continue;
                }
                foreach ($words as $w) {
                    if (str_contains($low, Str::lower(Str::ascii($w)))) {
                        $map[$field] = $h;
                        $used[] = $h;

                        continue 3;
                    }
                }
            }
        }

        return $map;
    }

    /** Campos que tienen que estar mapeados para poder importar. */
    public static function requiredFields(string $target): array
    {
        return match ($target) {
            'AGENDA' => ['title', 'date'],
            'TALLERISTAS' => ['name'],
            default => ['name'],
        };
    }

    // ── Importación ───────────────────────────────────────────────────────────

    /**
     * @param  array{headers: list<string>, rows: list<array<string, string>>}  $table
     */
    public function storeDataset(FormSource $source, array $table): void
    {
        Storage::disk(self::DISK)->put($this->datasetPath($source), json_encode($table, JSON_UNESCAPED_UNICODE));
        $mapping = $source->mapping ?: self::guessMapping($table['headers'], $source->target);
        $source->update(['headers' => $table['headers'], 'mapping' => array_intersect($mapping, $table['headers']) ?: $mapping]);
    }

    /** @return array{headers: list<string>, rows: list<array<string, string>>}|null */
    public function dataset(FormSource $source): ?array
    {
        $path = $this->datasetPath($source);
        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }
        $data = json_decode((string) Storage::disk(self::DISK)->get($path), true);

        return is_array($data) ? $data : null;
    }

    /** Descarga la planilla de Google, la guarda e importa las respuestas nuevas. */
    public function sync(FormSource $source): array
    {
        try {
            $this->storeDataset($source, $this->fetchSheet((string) $source->sheet_url));
        } catch (ValidationException $e) {
            $source->update(['last_error' => mb_substr(collect($e->errors())->flatten()->first() ?? 'Error', 0, 500)]);
            throw $e;
        }

        return $this->import($source->fresh());
    }

    /**
     * @return array{imported: int, errors: int, skipped: int}
     */
    public function import(FormSource $source): array
    {
        $table = $this->dataset($source);
        if ($table === null) {
            throw ValidationException::withMessages(['import' => 'Todavía no hay respuestas descargadas: sincronizá o subí el archivo.']);
        }
        $mapping = (array) $source->mapping;
        $missing = array_diff(self::requiredFields($source->target), array_keys(array_filter($mapping)));
        if ($missing !== []) {
            throw ValidationException::withMessages(['mapping' => 'Falta indicar la columna de: '.implode(', ', array_map(fn ($f) => FormSource::FIELDS[$f], $missing)).'.']);
        }

        $done = FormSourceRow::query()->where('form_source_id', $source->id)->whereIn('status', ['IMPORTADA', 'OMITIDA'])->pluck('row_hash')->flip();
        $result = ['imported' => 0, 'errors' => 0, 'skipped' => 0];
        foreach ($table['rows'] as $row) {
            $hash = sha1(json_encode(array_values($row), JSON_UNESCAPED_UNICODE));
            if (isset($done[$hash])) {
                continue;
            }
            $values = [];
            foreach ($mapping as $field => $header) {
                if ($header !== null && $header !== '' && array_key_exists($header, $row)) {
                    $values[$field] = trim((string) $row[$header]);
                }
            }
            try {
                [$status, $message, $contactId] = DB::transaction(fn () => $this->importRow($source, $values));
            } catch (ValidationException $e) {
                [$status, $message, $contactId] = ['ERROR', collect($e->errors())->flatten()->implode(' '), null];
            } catch (Throwable $e) {
                report($e);
                [$status, $message, $contactId] = ['ERROR', 'Error inesperado: '.$e->getMessage(), null];
            }
            FormSourceRow::query()->updateOrCreate(['form_source_id' => $source->id, 'row_hash' => $hash],
                ['data' => $row, 'status' => $status, 'message' => mb_substr((string) $message, 0, 500), 'contact_id' => $contactId]);
            $result[$status === 'IMPORTADA' ? 'imported' : ($status === 'ERROR' ? 'errors' : 'skipped')]++;
        }
        $source->update(['last_synced_at' => now(), 'last_error' => null]);

        if ($result['imported'] > 0) {
            $this->notifier->sendMany($this->directory->managers((int) $source->restaurant_id), 'inscripciones',
                "Formulario «{$source->name}»: {$result['imported']} respuestas nuevas",
                $source->targetLabel().($result['errors'] ? " · {$result['errors']} para revisar" : ''),
                route('forms.show', $source->id), null, (int) $source->restaurant_id);
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $v
     * @return array{0: string, 1: string, 2: ?int}
     */
    private function importRow(FormSource $source, array $v): array
    {
        $org = (int) $source->restaurant_id;
        $category = filled($v['category'] ?? null) ? ActivityCategory::findOrCreateByName($org, $v['category']) : $source->category;

        if ($source->target === 'AGENDA') {
            $date = self::parseDate($v['date'] ?? '');
            if ($date === null) {
                return ['ERROR', 'Fecha no reconocida: «'.($v['date'] ?? '').'».', null];
            }
            $time = self::parseTime($v['time'] ?? '');
            $exists = AgendaNotice::query()->where('restaurant_id', $org)->where('title', $v['title'])->whereDate('date', $date)->exists();
            if ($exists) {
                return ['OMITIDA', 'Ya estaba en la agenda.', null];
            }
            AgendaNotice::query()->create(['restaurant_id' => $org, 'kind' => $source->contact_kind && array_key_exists($source->contact_kind, AgendaNotice::KINDS) ? $source->contact_kind : 'AVISO',
                'title' => mb_substr($v['title'], 0, 255), 'body' => $v['description'] ?? null, 'date' => $date, 'time' => $time,
                'activity_category_id' => $category?->id, 'created_by' => $source->created_by]);

            return ['IMPORTADA', 'Agregada a la agenda el '.Carbon::parse($date)->format('d/m/Y').'.', null];
        }

        $contact = $this->contact($source, $v);

        if ($source->target === 'ALUMNOS') {
            $targets = $source->workshop_id ? Workshop::query()->whereKey($source->workshop_id)->get() : $this->matchWorkshops($org, $v['workshop'] ?? '');
            if ($targets->isEmpty()) {
                return ['ERROR', 'No encontré el taller «'.($v['workshop'] ?? '').'». Crealo o corregí el nombre y volvé a sincronizar.', $contact->id];
            }
            $msgs = [];
            foreach ($targets as $w) {
                $current = WorkshopEnrollment::query()->where('workshop_id', $w->id)->where('contact_id', $contact->id)->value('status');
                if (in_array($current, [WorkshopEnrollment::ACTIVA, WorkshopEnrollment::EN_ESPERA], true)) {
                    $msgs[] = "{$w->name}: ya estaba inscripto/a";

                    continue;
                }
                $e = $this->workshops->enroll($w, $contact, 'Formulario: '.$source->name);
                $msgs[] = $w->name.': '.($e->status === WorkshopEnrollment::ACTIVA ? 'inscripto/a' : "lista de espera #{$e->waitlist_position}");
            }

            return ['IMPORTADA', implode(' · ', $msgs), $contact->id];
        }

        if ($source->target === 'TALLERISTAS') {
            $title = filled($v['title'] ?? null) ? $v['title'] : "Propuesta de {$contact->name}";
            $exists = Workshop::query()->where('restaurant_id', $org)->where('name', $title)->where('teacher_contact_id', $contact->id)->exists();
            if (! $exists) {
                Workshop::query()->create(['restaurant_id' => $org, 'name' => mb_substr($title, 0, 255), 'status' => 'PROPUESTA',
                    'description' => $v['description'] ?? null, 'teacher_contact_id' => $contact->id, 'activity_category_id' => $category?->id]);
            }

            return ['IMPORTADA', $exists ? 'La propuesta ya estaba cargada; se actualizó el contacto.' : "Propuesta «{$title}» a evaluar.", $contact->id];
        }

        return ['IMPORTADA', 'Contacto actualizado.', $contact->id];
    }

    /** @param  array<string, string>  $v */
    private function contact(FormSource $source, array $v): Contact
    {
        $org = (int) $source->restaurant_id;
        $name = trim(($v['name'] ?? '').' '.($v['last_name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'La respuesta no tiene nombre.']);
        }
        $email = Str::lower($v['email'] ?? '');
        $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        $phone = filled($v['phone'] ?? null) ? mb_substr($v['phone'], 0, 40) : null;
        $kind = match ($source->target) {
            'ALUMNOS' => 'ALUMNO',
            'TALLERISTAS' => 'PROFESOR',
            default => $source->contact_kind && array_key_exists($source->contact_kind, Contact::KINDS) ? $source->contact_kind : 'PUBLICO',
        };

        $contact = ($email || $phone) ? Contact::query()->where('restaurant_id', $org)
            ->where(fn ($q) => $q->when($email, fn ($w) => $w->orWhere('email', $email))->when($phone, fn ($w) => $w->orWhere('phone', $phone)))
            ->first() : Contact::query()->where('restaurant_id', $org)->where('name', $name)->first();

        $attrs = array_filter(['email' => $email, 'phone' => $phone, 'document' => $v['document'] ?? null,
            'birth_date' => self::parseDate($v['birth_date'] ?? ''), 'notes' => $v['notes'] ?? null], fn ($x) => $x !== null && $x !== '');
        if (self::yes($v['consent'] ?? '')) {
            $attrs['accepts_marketing'] = true;
            $attrs['marketing_consent_at'] = now();
        }

        if ($contact) {
            // No pisa lo que la organización ya cargó: sólo completa lo vacío.
            $contact->fill(array_filter($attrs, fn ($val, $key) => blank($contact->getAttribute($key)) || $key === 'accepts_marketing' || $key === 'marketing_consent_at', ARRAY_FILTER_USE_BOTH))->save();
        } else {
            $contact = Contact::query()->create($attrs + ['restaurant_id' => $org, 'name' => mb_substr($name, 0, 255), 'kind' => $kind]);
        }

        $tags = collect(preg_split('/[,|]/', (string) $source->tags) ?: [])->map(fn ($t) => trim($t))->filter();
        if ($source->target === 'TALLERISTAS') {
            $tags->push('Postulante');
        }
        if ($tags->isNotEmpty()) {
            $contact->tags()->syncWithoutDetaching($tags->unique()->map(fn ($t) => ContactTag::query()->firstOrCreate(['restaurant_id' => $org, 'name' => mb_substr($t, 0, 60)])->id)->all());
        }

        return $contact;
    }

    /**
     * Talleres nombrados en la respuesta (Google Forms separa las casillas con coma).
     * Por cada opción gana el nombre exacto; si no hay, uno que lo contenga sólo si es el único.
     */
    private function matchWorkshops(int $org, string $value)
    {
        $all = Workshop::query()->where('restaurant_id', $org)->whereIn('status', ['ABIERTO', 'EN_CURSO'])->get()
            ->keyBy(fn (Workshop $w) => Str::lower(Str::ascii(trim($w->name))));
        $found = collect();
        foreach (preg_split('/[;,]\s*/', $value) ?: [] as $option) {
            $wanted = Str::lower(Str::ascii(trim($option)));
            if ($wanted === '') {
                continue;
            }
            if ($all->has($wanted)) {
                $found->push($all[$wanted]);

                continue;
            }
            $partial = $all->filter(fn ($w, $name) => str_contains($name, $wanted) || str_contains($wanted, $name));
            if ($partial->count() === 1) {
                $found->push($partial->first());
            }
        }

        return $found->unique('id')->values();
    }

    public static function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 80000) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }
        $datePart = preg_split('/\s+/', $value)[0];
        foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'd-m-Y', 'd.m.Y', 'Y/m/d'] as $format) {
            try {
                $d = Carbon::createFromFormat('!'.$format, $datePart);
            } catch (Throwable) {
                continue;
            }
            if ($d instanceof Carbon && $d->year >= 1900 && $d->year <= 2200) {
                return $d->toDateString();
            }
        }

        return null;
    }

    public static function parseTime(string $value): ?string
    {
        if (preg_match('/(\d{1,2})[:.h](\d{2})/', $value, $m) && (int) $m[1] < 24 && (int) $m[2] < 60) {
            return sprintf('%02d:%02d', $m[1], $m[2]);
        }
        if (preg_match('/^(\d{1,2})\s*(h|hs)?$/i', trim($value), $m) && (int) $m[1] < 24) {
            return sprintf('%02d:00', $m[1]);
        }

        return null;
    }

    private static function yes(string $value): bool
    {
        $v = Str::lower(Str::ascii(trim($value)));

        return $v !== '' && (str_starts_with($v, 'si') || str_starts_with($v, 'acepto') || in_array($v, ['1', 'x', 'true', 'yes'], true));
    }

    private function datasetPath(FormSource $source): string
    {
        return "forms/{$source->restaurant_id}/{$source->id}.json";
    }
}

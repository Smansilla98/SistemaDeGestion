<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Contact;
use App\Models\ContactTag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Segmentación, importación y exportación de contactos de una organización.
 */
final class ContactService
{
    public const CSV_COLUMNS = ['nombre', 'tipo', 'email', 'telefono', 'alias', 'etiquetas', 'acepta_difusion', 'notas'];

    /**
     * @param  array<string, mixed>  $f  q, kind, tag_id, group_id, workshop_id, marketing
     * @return Builder<Contact>
     */
    public function query(int $restaurantId, array $f): Builder
    {
        $q = Contact::query()->where('restaurant_id', $restaurantId);

        if (filled($f['q'] ?? null)) {
            $term = '%'.trim((string) $f['q']).'%';
            $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('alias', 'like', $term)
                ->orWhere('email', 'like', $term)->orWhere('phone', 'like', $term));
        }
        if (filled($f['kind'] ?? null)) {
            $q->whereIn('kind', (array) $f['kind']);
        }
        if (filled($f['tag_id'] ?? null)) {
            $q->whereHas('tags', fn ($t) => $t->whereIn('contact_tags.id', (array) $f['tag_id']));
        }
        if (filled($f['group_id'] ?? null)) {
            $q->whereHas('groups', fn ($g) => $g->whereIn('contact_groups.id', (array) $f['group_id']));
        }
        if (filled($f['workshop_id'] ?? null)) {
            $q->whereHas('enrollments', fn ($e) => $e->whereIn('workshop_id', (array) $f['workshop_id'])->where('status', 'ACTIVA'));
        }
        if (! empty($f['marketing'])) {
            $q->where('accepts_marketing', true)->where('is_active', true);
        }

        return $q;
    }

    /**
     * Importa un CSV (UTF-8, separado por coma o punto y coma). Un contacto con el mismo email
     * o teléfono se actualiza en lugar de duplicarse.
     *
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function import(int $restaurantId, string $path): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            $result['errors'][] = 'No se pudo leer el archivo.';

            return $result;
        }
        $first = (string) fgets($handle);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $header = array_map(fn ($h) => Str::of((string) $h)->replace("\u{FEFF}", '')->trim()->lower()->ascii()->replace(' ', '_')->toString(), str_getcsv($first, $delimiter));
        if (! in_array('nombre', $header, true)) {
            fclose($handle);
            $result['errors'][] = 'Falta la columna «nombre». Columnas esperadas: '.implode(', ', self::CSV_COLUMNS).'.';

            return $result;
        }

        $line = 1;
        DB::transaction(function () use ($handle, $delimiter, $header, $restaurantId, &$result, &$line) {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $line++;
                if ($row === [null] || count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }
                $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));
                $name = trim((string) ($data['nombre'] ?? ''));
                $email = Str::lower(trim((string) ($data['email'] ?? '')));
                if ($name === '' || ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL))) {
                    $result['skipped']++;
                    $result['errors'][] = "Fila {$line}: ".($name === '' ? 'sin nombre' : "email inválido ({$email})").'.';

                    continue;
                }
                $phone = trim((string) ($data['telefono'] ?? ''));
                $kind = Str::upper(Str::ascii(trim((string) ($data['tipo'] ?? ''))));
                $kind = array_key_exists($kind, Contact::KINDS) ? $kind : 'PUBLICO';
                $accepts = in_array(Str::lower(trim((string) ($data['acepta_difusion'] ?? ''))), ['si', 'sí', '1', 'true', 'x'], true);

                $contact = Contact::query()->where('restaurant_id', $restaurantId)
                    ->where(fn ($q) => $q->when($email !== '', fn ($w) => $w->orWhere('email', $email))
                        ->when($phone !== '', fn ($w) => $w->orWhere('phone', $phone)))
                    ->when($email === '' && $phone === '', fn ($q) => $q->where('name', $name))
                    ->first();

                $attrs = array_filter([
                    'name' => $name, 'kind' => $kind, 'email' => $email ?: null, 'phone' => $phone ?: null,
                    'alias' => trim((string) ($data['alias'] ?? '')) ?: null, 'notes' => trim((string) ($data['notas'] ?? '')) ?: null,
                ], fn ($v) => $v !== null);
                if ($accepts) {
                    $attrs['accepts_marketing'] = true;
                    $attrs['marketing_consent_at'] = now();
                }

                if ($contact) {
                    $contact->update($attrs);
                    $result['updated']++;
                } else {
                    $contact = Contact::query()->create($attrs + ['restaurant_id' => $restaurantId]);
                    $result['created']++;
                }

                $tags = array_filter(array_map('trim', preg_split('/[|,]/', (string) ($data['etiquetas'] ?? '')) ?: []));
                if ($tags !== []) {
                    $ids = collect($tags)->map(fn ($t) => ContactTag::query()->firstOrCreate(['restaurant_id' => $restaurantId, 'name' => mb_substr($t, 0, 60)])->id);
                    $contact->tags()->syncWithoutDetaching($ids->all());
                }
            }
        });
        fclose($handle);

        return $result;
    }

    /**
     * @param  Builder<Contact>  $query
     * @return \Generator<int, list<string>>
     */
    public function exportRows(Builder $query): \Generator
    {
        yield self::CSV_COLUMNS;
        foreach ($query->with('tags')->orderBy('name')->lazy() as $c) {
            yield [$c->name, $c->kind, (string) $c->email, (string) $c->phone, (string) $c->alias,
                $c->tags->pluck('name')->implode('|'), $c->accepts_marketing ? 'si' : 'no', (string) $c->notes];
        }
    }
}

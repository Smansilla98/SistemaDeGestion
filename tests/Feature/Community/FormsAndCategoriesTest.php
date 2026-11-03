<?php

use App\Models\ActivityCategory;
use App\Models\AgendaNotice;
use App\Models\Contact;
use App\Models\FormSource;
use App\Models\Workshop;
use App\Models\WorkshopEnrollment;
use App\Services\Forms\FormImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    Storage::fake('local');
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
    $this->actingAs($this->admin);
});

const SHEET = 'https://docs.google.com/spreadsheets/d/AbC123_x/edit#gid=777';

function googleCsv(array $rows): string
{
    $lines = ['Marca temporal,Nombre y apellido,Dirección de correo electrónico,WhatsApp,¿A qué taller te anotás?,¿Querés recibir novedades?'];
    foreach ($rows as $r) {
        $lines[] = implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', $v).'"', $r));
    }

    return implode("\n", $lines);
}

it('convierte links de Google Sheets y rechaza otros', function () {
    expect(FormImportService::csvUrl(SHEET))->toBe('https://docs.google.com/spreadsheets/d/AbC123_x/export?format=csv&gid=777')
        ->and(FormImportService::csvUrl('https://docs.google.com/spreadsheets/d/e/2PACX-1v/pubhtml?gid=0&single=true'))
        ->toBe('https://docs.google.com/spreadsheets/d/e/2PACX-1v/pub?gid=0&single=true&output=csv');
    expect(fn () => FormImportService::csvUrl('https://evil.example.com/spreadsheets/d/x'))->toThrow(ValidationException::class);
    expect(fn () => FormImportService::csvUrl('https://docs.google.com/forms/d/xyz/viewform'))->toThrow(ValidationException::class);
});

it('sincroniza un Google Form de inscripción: inscribe, no duplica y reintenta los talleres que faltaban', function () {
    $perc = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Percusión', 'status' => 'ABIERTO']);
    $teatro = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Teatro comunitario', 'status' => 'ABIERTO']);
    Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Teatro comunitario avanzado', 'status' => 'ABIERTO']); // no debe confundirse
    $rows = [
        ['29/10/2026 10:00:00', 'Ana Pérez', 'ana@test.com', '11 5555 0001', 'Percusión, Teatro comunitario', 'Sí, acepto'],
        ['29/10/2026 11:00:00', 'Beto Gil', 'beto@test.com', '11 5555 0002', 'Danza', 'No'],
    ];
    Http::fake(['docs.google.com/*' => Http::sequence()->push(googleCsv($rows))->push(googleCsv($rows))->push(googleCsv($rows))]);

    $this->post(route('forms.store'), ['name' => 'Inscripciones 2027', 'target' => 'ALUMNOS', 'sheet_url' => SHEET, 'auto_sync' => 1, 'tags' => 'form 2027'])->assertRedirect();
    $source = FormSource::query()->firstOrFail();
    expect($source->mapping)->toMatchArray(['name' => 'Nombre y apellido', 'email' => 'Dirección de correo electrónico',
        'phone' => 'WhatsApp', 'workshop' => '¿A qué taller te anotás?', 'consent' => '¿Querés recibir novedades?']);
    Http::assertSent(fn ($r) => $r->url() === 'https://docs.google.com/spreadsheets/d/AbC123_x/export?format=csv&gid=777');

    $this->post(route('forms.mapping', $source), ['mapping' => $source->mapping])->assertSessionHas('success', 'Importadas: 1 · para revisar: 1 · omitidas: 0.');
    $ana = Contact::query()->where('email', 'ana@test.com')->first();
    expect($ana->kind)->toBe('ALUMNO')->and($ana->accepts_marketing)->toBeTrue()->and($ana->tags->pluck('name')->all())->toBe(['form 2027'])
        ->and(WorkshopEnrollment::query()->where('contact_id', $ana->id)->count())->toBe(2);
    expect(notificationsTitled($this->admin, 'Inscripciones 2027'))->toBe(1);

    // Se crea el taller que faltaba y se vuelve a sincronizar: Ana no se repite, Beto se inscribe.
    Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Danza', 'status' => 'ABIERTO']);
    $this->post(route('forms.sync', $source))->assertSessionHas('success', 'Importadas: 1 · para revisar: 0 · omitidas: 0.');
    expect(Contact::query()->count())->toBe(2)->and(WorkshopEnrollment::query()->count())->toBe(3)
        ->and($source->rows()->where('status', 'ERROR')->count())->toBe(0);

    $this->artisan('forms:sync')->assertSuccessful();
    expect(WorkshopEnrollment::query()->count())->toBe(3);
    $this->get(route('forms.show', $source))->assertOk()->assertSee('Ana Pérez')->assertSee('Teatro comunitario: inscripto/a');
});

it('avisa cuando la planilla no está compartida', function () {
    Http::fake(['docs.google.com/*' => Http::response('<!DOCTYPE html><html>login</html>', 200)]);
    $this->post(route('forms.store'), ['name' => 'X', 'target' => 'CONTACTOS', 'sheet_url' => SHEET])->assertSessionHasErrors('sheet_url');
});

it('importa propuestas de talleristas desde un Excel y crea categorías', function () {
    $book = new Spreadsheet;
    $book->getActiveSheet()->fromArray([
        ['Marca temporal', 'Nombre', 'Apellido', 'Correo', 'Nombre de la propuesta', 'Contanos de qué se trata', 'Disciplina'],
        ['28/10/2026 09:00', 'Lu', 'Martínez', 'lu@test.com', 'Clown para chicos', 'Juego y teatro físico', 'Teatro'],
    ]);
    $path = tempnam(sys_get_temp_dir(), 'x').'.xlsx';
    (new Xlsx($book))->save($path);

    $this->post(route('forms.store'), ['name' => 'Convocatoria talleristas', 'target' => 'TALLERISTAS',
        'file' => new UploadedFile($path, 'respuestas.xlsx', null, null, true)])->assertRedirect();
    $source = FormSource::query()->firstOrFail();
    expect($source->mapping)->toMatchArray(['name' => 'Nombre', 'last_name' => 'Apellido', 'title' => 'Nombre de la propuesta', 'category' => 'Disciplina']);
    $this->post(route('forms.mapping', $source), ['mapping' => $source->mapping]);

    $ws = Workshop::query()->where('name', 'Clown para chicos')->firstOrFail();
    expect($ws->status)->toBe('PROPUESTA')->and($ws->category->name)->toBe('Teatro')
        ->and($ws->teacher->name)->toBe('Lu Martínez')->and($ws->teacher->kind)->toBe('PROFESOR')
        ->and($ws->teacher->tags->pluck('name')->all())->toContain('Postulante');
    $this->get(route('workshops.index'))->assertSee('Clown para chicos')->assertSee('Propuesta');
});

it('suma actividades a la agenda desde un CSV y la agenda se filtra por tipo y categoría', function () {
    $csv = "Marca temporal;Actividad;Fecha;Hora;Categoría\n1/11/2026 10:00;Feria verde;".today()->addDay()->format('d/m/Y').";15 hs;Feria\n2/11/2026;Sin fecha;mañana;;\n";
    $this->post(route('forms.store'), ['name' => 'Propuestas de actividades', 'target' => 'AGENDA', 'contact_kind' => 'FERIA',
        'file' => UploadedFile::fake()->createWithContent('agenda.csv', $csv)]);
    $source = FormSource::query()->firstOrFail();
    $this->post(route('forms.mapping', $source), ['mapping' => ['title' => 'Actividad', 'date' => 'Fecha', 'time' => 'Hora', 'category' => 'Categoría']])
        ->assertSessionHas('success', 'Importadas: 1 · para revisar: 1 · omitidas: 0.');
    $notice = AgendaNotice::query()->firstOrFail();
    expect($notice->kind)->toBe('FERIA')->and(substr((string) $notice->time, 0, 5))->toBe('15:00')->and($notice->category->name)->toBe('Feria');

    $musica = ActivityCategory::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Música', 'color' => '#112233']);
    $w = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Guitarra', 'status' => 'ABIERTO', 'activity_category_id' => $musica->id]);
    $w->sessions()->create(['date' => today()->addDay(), 'start_time' => '18:00', 'end_time' => '19:00', 'status' => 'PROGRAMADA']);

    $this->get(route('agenda.index'))->assertSee('Feria verde')->assertSee('Guitarra');
    $titles = fn (array $f) => app(\App\Services\Community\AgendaService::class)
        ->between($this->admin, $this->org->id, \Carbon\CarbonImmutable::today(), \Carbon\CarbonImmutable::today()->addDays(6), $f)
        ->flatten(1)->pluck('title')->all();
    expect($titles(['category_id' => $musica->id]))->toBe(['Guitarra'])
        ->and($titles(['types' => ['Aviso']]))->toBe(['Feria verde'])
        ->and($titles(['types' => ['Clase', 'Aviso']]))->toHaveCount(2);
    $this->get(route('agenda.index', ['tipo' => ['Aviso']]))->assertOk()->assertDontSee('Guitarra');
});

it('las categorías se administran y sólo gestión ve los formularios', function () {
    $this->post(route('agenda.categories.store'), ['name' => 'Música', 'color' => '#8a5cc2'])->assertSessionHasNoErrors();
    $this->post(route('agenda.categories.store'), ['name' => 'Música', 'color' => '#8a5cc2'])->assertSessionHasErrors('name');
    $this->get(route('agenda.categories.index'))->assertOk()->assertSee('Música');

    $profe = orgUser(communityOrg(), 'MOZO', ['username' => 'profe9']);
    app(\App\Services\Community\MembershipService::class)->add($this->org->id, 'profe9', 'PROFESOR', $this->admin);
    $this->actingAs($profe)->post(route('branch.store'), ['restaurant_id' => $this->org->id]);
    $this->get(route('forms.index'))->assertForbidden();
    $this->get(route('agenda.categories.index'))->assertForbidden();

    $other = FormSource::query()->create(['restaurant_id' => communityOrg()->id, 'name' => 'Ajeno', 'target' => 'CONTACTOS']);
    $this->actingAs($this->admin)->get(route('forms.show', $other))->assertNotFound();
});

it('la demo de centro cultural trae un formulario de ejemplo con una respuesta para revisar', function () {
    config(['app.demo_entry' => true]);
    $this->post(route('entry.culture'))->assertRedirect();
    $source = FormSource::query()->where('name', 'like', '%(ejemplo)%')->firstOrFail();
    expect($source->rows()->where('status', 'IMPORTADA')->count())->toBe(2)->and($source->rows()->where('status', 'ERROR')->count())->toBe(1);
    $this->get(route('agenda.index', ['vista' => 'mes']))->assertOk()->assertSee('Música');
});

<?php

use App\Models\ActivityEntry;
use App\Models\Contact;
use App\Models\DistributionRule;
use App\Models\Document;
use App\Models\Entrepreneur;
use App\Models\Event;
use App\Models\Workshop;
use App\Services\Community\MembershipService;
use App\Services\Community\WorkshopService;
use App\Services\Finance\ActivityFinanceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    Storage::fake('local');
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
    $this->actingAs($this->admin);
});

it('documentos: versiones, visibilidad y descarga autorizada en el servidor', function () {
    $alumno = orgUser(communityOrg(), 'MOZO', ['username' => 'alu']);
    app(MembershipService::class)->add($this->org->id, 'alu', 'ALUMNO', $this->admin);

    $this->post(route('documents.store'), ['title' => 'Reglamento', 'category' => 'REGLAMENTO', 'visibility' => 'MIEMBROS',
        'file' => UploadedFile::fake()->create('reglamento.pdf', 10, 'application/pdf')])->assertRedirect();
    $this->post(route('documents.store'), ['title' => 'Contrato interno', 'category' => 'CONTRATO', 'visibility' => 'GESTION', 'publish' => 1,
        'file' => UploadedFile::fake()->create('contrato.pdf', 10, 'application/pdf')])->assertRedirect();
    $regl = Document::query()->where('title', 'Reglamento')->first();
    $contrato = Document::query()->where('title', 'Contrato interno')->first();
    $this->post(route('documents.versions.store', $regl), ['file' => UploadedFile::fake()->create('reglamento-v2.pdf', 12, 'application/pdf')]);
    expect($regl->versions()->count())->toBe(2);

    // Borrador: el alumno no lo ve. Publicado para miembros: lo ve, se le avisa y lo descarga.
    $this->actingAs($alumno)->post(route('branch.store'), ['restaurant_id' => $this->org->id]);
    $this->get(route('documents.show', $regl))->assertForbidden();
    $this->actingAs($this->admin)->put(route('documents.update', $regl), ['title' => 'Reglamento', 'category' => 'REGLAMENTO', 'visibility' => 'MIEMBROS', 'status' => 'PUBLICADO']);
    expect(notificationsTitled($alumno, 'Nuevo documento'))->toBe(1);

    $this->actingAs($alumno)->post(route('branch.store'), ['restaurant_id' => $this->org->id]);
    $this->get(route('documents.index'))->assertSee('Reglamento')->assertDontSee('Contrato interno');
    $this->get(route('documents.download', [$regl, $regl->versions()->first()]))->assertOk()->assertDownload('reglamento-v2.pdf');
    $this->get(route('documents.download', [$contrato, $contrato->versions()->first()]))->assertNotFound();

    $this->actingAs(orgUser(communityOrg()))->get(route('documents.show', $regl))->assertNotFound();
});

it('genera el manual de marca del emprendedor en PDF como versión nueva', function () {
    $ent = Entrepreneur::query()->create(['restaurant_id' => $this->org->id, 'business_name' => 'Dulces Ana',
        'brand' => ['tagline' => 'Hecho a mano', 'colors' => ['#AA3355', '#FFEEDD'], 'font_primary' => 'Lora']]);

    $this->post(route('market.entrepreneurs.brand-manual', $ent))->assertRedirect();
    $this->post(route('market.entrepreneurs.brand-manual', $ent))->assertRedirect();

    $doc = Document::query()->where('entrepreneur_id', $ent->id)->where('category', 'MANUAL_MARCA')->sole();
    $v = $doc->latestVersion();
    expect($v->version)->toBe(2)->and($v->mime)->toBe('application/pdf');
    expect(Storage::disk('local')->get($v->path))->toStartWith('%PDF');
});

it('reparte 70/30 sobre el neto sin perder centavos y no deja pagar de más', function () {
    $rule = DistributionRule::query()->create(['restaurant_id' => $this->org->id, 'name' => '70/30', 'base' => 'NETO',
        'parties' => ActivityFinanceService::normalizeParties([['label' => 'Docente', 'percent' => '70'], ['label' => 'Espacio', 'percent' => '30']])]);
    $ws = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Cerámica', 'status' => 'EN_CURSO', 'fee_cents' => 1000000, 'distribution_rule_id' => $rule->id]);
    $svc = app(ActivityFinanceService::class);
    $ana = Contact::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Ana', 'kind' => 'ALUMNO']);
    $enr = app(WorkshopService::class)->enroll($ws, $ana);

    $this->post(route('finance.activity.entries.store', ['workshop', $ws->id]), ['type' => 'INGRESO', 'concept' => 'Cuota', 'amount' => '6.000,01', 'date' => today()->toDateString(), 'workshop_enrollment_id' => $enr->id])->assertSessionHasNoErrors();
    $svc->record($ws, 'INGRESO', 'Cuota', 500000, today()->toDateString(), $this->admin);
    $gasto = $svc->record($ws, 'GASTO', 'Arcilla', 100000, today()->toDateString(), $this->admin);
    $svc->record($ws, 'GASTO', 'Error', 999999, today()->toDateString(), $this->admin);
    $svc->void(ActivityEntry::query()->where('concept', 'Error')->first(), 'Cargado dos veces');

    $s = $svc->settlement($ws);
    // neto = 1.100.001 − 100.000 = 1.000.001 → 700.001 / 300.000 (suma exacta)
    expect($s['income_cents'])->toBe(1100001)->and($s['expense_cents'])->toBe(100000)->and($s['base_cents'])->toBe(1000001)
        ->and(array_column($s['parties'], 'due_cents'))->toBe([700001, 300000]);
    expect($svc->enrollmentBalances($ws)[$enr->id]['pending_cents'])->toBe(399999);

    $svc->payout($ws, 'Docente', 500000, today()->toDateString(), $this->admin);
    expect(fn () => $svc->payout($ws, 'Docente', 200002, today()->toDateString(), $this->admin))->toThrow(ValidationException::class);
    expect(fn () => $svc->payout($ws, 'Otro', 1, today()->toDateString(), $this->admin))->toThrow(ValidationException::class);
    expect($svc->settlement($ws)['parties'][0]['pending_cents'])->toBe(200001);

    $this->get(route('finance.activity', ['workshop', $ws->id]))->assertOk()->assertSee('Docente')->assertSee('$7.000,01');
    expect($gasto->fresh()->voided_at)->toBeNull();
});

it('valida las reglas de reparto y reparte con resto mayor', function () {
    expect(fn () => ActivityFinanceService::normalizeParties([['label' => 'A', 'percent' => 60], ['label' => 'B', 'percent' => 30]]))->toThrow(ValidationException::class);
    expect(fn () => ActivityFinanceService::normalizeParties([['label' => 'A', 'percent' => 100]]))->toThrow(ValidationException::class);
    expect(ActivityFinanceService::split(100, [33.33, 33.33, 33.34]))->toBe([33, 33, 34]);
    expect(ActivityFinanceService::split(1, [50, 50]))->toBe([1, 0]);
    expect(ActivityFinanceService::toCents('1.234,56'))->toBe(123456)->and(ActivityFinanceService::toCents('1234.5'))->toBe(123450);

    $this->post(route('finance.rules.store'), ['name' => 'Mal', 'base' => 'BRUTO', 'parties' => [['label' => 'A', 'percent' => 50], ['label' => 'B', 'percent' => 40]]])
        ->assertSessionHasErrors('parties');
});

it('etapas de evento: presupuesto, pagos parciales y saldo pendiente', function () {
    $event = Event::query()->create(['restaurant_id' => $this->org->id, 'created_by' => $this->admin->id, 'name' => 'Festival', 'date' => today()->addWeek(), 'status' => 'PROGRAMADO']);
    $this->post(route('events.stages.store', $event), ['name' => 'Sonido', 'budget' => '200.000', 'status' => 'PENDIENTE'])->assertSessionHasNoErrors();
    $stage = $event->stages()->first();
    $svc = app(ActivityFinanceService::class);
    $svc->record($event, 'GASTO', 'Seña sonido', 5000000, today()->toDateString(), $this->admin, ['event_stage_id' => $stage->id]);

    $sum = $svc->stagesSummary($event);
    expect($sum['budget_cents'])->toBe(20000000)->and($sum['paid_cents'])->toBe(5000000)->and($sum['pending_cents'])->toBe(15000000);
    $this->get(route('events.show', $event))->assertOk()->assertSee('Sonido')->assertSee('$150.000,00');

    $other = Event::query()->create(['restaurant_id' => $this->org->id, 'created_by' => $this->admin->id, 'name' => 'Otro', 'date' => today(), 'status' => 'PROGRAMADO']);
    expect(fn () => $svc->record($other, 'GASTO', 'x', 1, today()->toDateString(), $this->admin, ['event_stage_id' => $stage->id]))->toThrow(ValidationException::class);
});

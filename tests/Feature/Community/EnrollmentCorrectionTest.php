<?php

use App\Models\Contact;
use App\Models\Workshop;
use App\Models\WorkshopEnrollment;
use App\Services\Finance\ActivityFinanceService;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
    $this->ws = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Fotografía', 'status' => 'ABIERTO', 'capacity' => 1]);
    $this->contact = Contact::query()->create(['restaurant_id' => $this->org->id, 'kind' => 'ALUMNO', 'name' => 'Victoria Rodrigez', 'phone' => '111']);
    $this->enrollment = WorkshopEnrollment::query()->create(['workshop_id' => $this->ws->id, 'contact_id' => $this->contact->id, 'status' => 'ACTIVA', 'enrolled_at' => now()]);
});

it('el admin corrige los datos de la persona, la nota y la fecha de una inscripción', function () {
    $this->actingAs($this->admin)->put(route('workshops.enrollments.update', [$this->ws, $this->enrollment]), [
        'name' => 'Victoria Rodríguez Mongelli', 'phone' => '1134053189', 'email' => 'vic@example.com',
        'status' => 'ACTIVA', 'enrolled_at' => '2026-10-01', 'note' => 'Paga en efectivo',
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    expect($this->contact->fresh()->name)->toBe('Victoria Rodríguez Mongelli')->and($this->contact->fresh()->phone)->toBe('1134053189')
        ->and($this->enrollment->fresh()->note)->toBe('Paga en efectivo')
        ->and($this->enrollment->fresh()->enrolled_at->toDateString())->toBe('2026-10-01');
});

it('el admin reactiva una inscripción cancelada por error y pasa otra a lista de espera', function () {
    $this->enrollment->update(['status' => 'CANCELADA', 'cancelled_at' => now()]);
    $base = ['name' => 'Victoria', 'phone' => null, 'email' => null];

    $this->actingAs($this->admin)->put(route('workshops.enrollments.update', [$this->ws, $this->enrollment]), $base + ['status' => 'ACTIVA'])->assertSessionHasNoErrors();
    expect($this->enrollment->fresh()->status)->toBe('ACTIVA')->and($this->enrollment->fresh()->cancelled_at)->toBeNull();

    $this->actingAs($this->admin)->put(route('workshops.enrollments.update', [$this->ws, $this->enrollment]), $base + ['status' => 'EN_ESPERA'])->assertSessionHasNoErrors();
    expect($this->enrollment->fresh()->status)->toBe('EN_ESPERA')->and($this->enrollment->fresh()->waitlist_position)->toBe(1);
});

it('borra una inscripción cargada por error, salvo que tenga pagos', function () {
    $this->ws->entries()->create(['restaurant_id' => $this->org->id, 'type' => 'INGRESO', 'amount_cents' => 1000, 'concept' => 'Cuota',
        'date' => today(), 'workshop_enrollment_id' => $this->enrollment->id, 'created_by' => $this->admin->id]);
    $this->actingAs($this->admin)->delete(route('workshops.enrollments.destroy', [$this->ws, $this->enrollment]))->assertSessionHas('error');
    expect(WorkshopEnrollment::query()->find($this->enrollment->id))->not->toBeNull();

    $this->ws->entries()->update(['voided_at' => now()]);
    $this->actingAs($this->admin)->delete(route('workshops.enrollments.destroy', [$this->ws, $this->enrollment]))->assertSessionHas('success');
    expect(WorkshopEnrollment::query()->find($this->enrollment->id))->toBeNull();
});

it('quien no administra no puede corregir inscripciones', function () {
    $alumno = orgUser($this->org, 'ALUMNO');
    $this->actingAs($alumno)->put(route('workshops.enrollments.update', [$this->ws, $this->enrollment]), ['name' => 'X', 'status' => 'CANCELADA'])->assertForbidden();
});

it('el arancel acepta el signo de pesos y el punto de miles', function () {
    expect(ActivityFinanceService::toCents('$ 25.000'))->toBe(2500000)->and(ActivityFinanceService::toCents('25.000,50'))->toBe(2500050)
        ->and(ActivityFinanceService::toCents('25'))->toBe(2500);
});

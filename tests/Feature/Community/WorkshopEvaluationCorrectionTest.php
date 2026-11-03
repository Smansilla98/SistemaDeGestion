<?php

use App\Models\Contact;
use App\Models\Workshop;
use App\Models\WorkshopEnrollment;
use App\Models\WorkshopEvaluation;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
    $this->ws = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Teatro', 'status' => 'EN_CURSO']);
    $contact = Contact::query()->create(['restaurant_id' => $this->org->id, 'kind' => 'ALUMNO', 'name' => 'Ana']);
    WorkshopEnrollment::query()->create(['workshop_id' => $this->ws->id, 'contact_id' => $contact->id, 'status' => 'ACTIVA', 'enrolled_at' => now()]);
    $this->ev = WorkshopEvaluation::query()->create(['workshop_id' => $this->ws->id, 'contact_id' => $contact->id, 'title' => 'Muestar final',
        'grade' => '7', 'evaluated_on' => '2026-11-01', 'recorded_by' => $this->admin->id]);
});

it('se corrige una evaluación mal cargada', function () {
    $this->actingAs($this->admin)->put(route('workshops.evaluations.update', [$this->ws, $this->ev]), [
        'title' => 'Muestra final', 'grade' => '9', 'evaluated_on' => '2026-11-02', 'comment' => 'Muy buena presencia escénica',
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    expect($this->ev->fresh())->title->toBe('Muestra final')->grade->toBe('9')
        ->and($this->ev->fresh()->evaluated_on->toDateString())->toBe('2026-11-02');
});

it('se borra una evaluación cargada por error', function () {
    $this->actingAs($this->admin)->delete(route('workshops.evaluations.destroy', [$this->ws, $this->ev]))->assertSessionHas('success');
    expect(WorkshopEvaluation::query()->find($this->ev->id))->toBeNull();
});

it('no se toca una evaluación de otro taller ni sin permiso', function () {
    $otro = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Danza', 'status' => 'EN_CURSO']);
    $this->actingAs($this->admin)->delete(route('workshops.evaluations.destroy', [$otro, $this->ev]))->assertNotFound();
    $this->actingAs(orgUser($this->org, 'COCINA'))->delete(route('workshops.evaluations.destroy', [$this->ws, $this->ev]))->assertForbidden();
    expect(WorkshopEvaluation::query()->find($this->ev->id))->not->toBeNull();
});

it('la matriz de permisos y el alta de usuarios ofrecen los roles del espacio cultural', function () {
    $alumna = orgUser($this->org, 'ALUMNO');
    expect($alumna->fresh()->role)->toBe('ALUMNO')
        ->and(\App\Models\User::getAssignableRoles($this->admin))->toContain('COORDINADOR', 'PROFESOR', 'ALUMNO', 'EMPRENDEDOR', 'MARKETING');
});

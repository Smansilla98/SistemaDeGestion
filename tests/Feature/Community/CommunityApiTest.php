<?php

use App\Models\Contact;
use App\Models\Workshop;
use App\Models\WorkshopAttendance;
use App\Models\WorkshopEnrollment;
use App\Models\WorkshopSession;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->org = communityOrg();
    $this->profeUser = orgUser($this->org, 'PROFESOR', ['username' => 'profe_api']);
    $profe = Contact::query()->create(['restaurant_id' => $this->org->id, 'kind' => 'PROFESOR', 'name' => 'Marta Profe', 'user_id' => $this->profeUser->id]);
    $this->ws = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Percusión', 'status' => 'EN_CURSO', 'teacher_contact_id' => $profe->id]);
    $this->otro = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Danza', 'status' => 'EN_CURSO']);
    $this->ana = Contact::query()->create(['restaurant_id' => $this->org->id, 'kind' => 'ALUMNO', 'name' => 'Ana']);
    WorkshopEnrollment::query()->create(['workshop_id' => $this->ws->id, 'contact_id' => $this->ana->id, 'status' => 'ACTIVA', 'enrolled_at' => now()]);
    $this->clase = WorkshopSession::query()->create(['workshop_id' => $this->ws->id, 'date' => today()->addDay()->toDateString(), 'start_time' => '18:00', 'end_time' => '20:00', 'status' => 'PROGRAMADA']);
});

function tokenFor(string $username): array
{
    $token = test()->postJson('/api/auth/login', ['username' => $username, 'password' => 'password123'])->assertOk()->json('data.access_token');

    return ['Authorization' => 'Bearer '.$token];
}

it('la profe ve en la app su agenda y sólo sus talleres', function () {
    $h = tokenFor('profe_api');
    $this->withHeaders($h)->getJson('/api/agenda')->assertOk()->assertJsonPath('data.days.0.items.0.title', 'Percusión');
    $this->withHeaders($h)->getJson('/api/workshops')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Percusión');
    $this->withHeaders($h)->getJson("/api/workshops/{$this->otro->id}")->assertNotFound();
    $this->withHeaders($h)->getJson("/api/workshops/{$this->ws->id}")->assertOk()->assertJsonPath('data.sessions.0.id', $this->clase->id);
});

it('la profe toma asistencia desde la app', function () {
    $h = tokenFor('profe_api');
    $url = "/api/workshops/{$this->ws->id}/sessions/{$this->clase->id}/attendance";
    $this->withHeaders($h)->getJson($url)->assertOk()->assertJsonPath('data.students.0.name', 'Ana');
    $this->withHeaders($h)->postJson($url, ['statuses' => [$this->ana->id => 'PRESENTE']])->assertOk();

    expect(WorkshopAttendance::query()->where('workshop_session_id', $this->clase->id)->value('status'))->toBe('PRESENTE');
});

it('un alumno ve la agenda pero no gestiona talleres', function () {
    orgUser($this->org, 'ALUMNO', ['username' => 'alumna_api']);
    $h = tokenFor('alumna_api');
    $this->withHeaders($h)->getJson('/api/agenda')->assertOk();
    $this->withHeaders($h)->getJson('/api/workshops')->assertForbidden();
    $this->withHeaders($h)->postJson("/api/workshops/{$this->ws->id}/sessions/{$this->clase->id}/attendance", ['statuses' => []])->assertForbidden();
});

it('sin el módulo de talleres la API no responde', function () {
    app(\App\Services\ModuleLicenseService::class)->save($this->org, ['workshops' => false]);
    $this->withHeaders(tokenFor('profe_api'))->getJson('/api/agenda')->assertForbidden();
});

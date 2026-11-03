<?php

use App\Models\Event;
use App\Models\Workshop;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
});

it('el calendario de eventos es la agenda: abre el mes pedido filtrado en eventos', function () {
    $this->actingAs($this->admin)->get(route('events.index', ['year' => 2026, 'month' => 11]))
        ->assertRedirect(route('agenda.index', ['vista' => 'mes', 'desde' => '2026-11-01', 'tipo' => ['Evento', 'Etapa']]));
});

it('la agenda mensual es una grilla con clases y eventos, y cada día crea un evento', function () {
    $ws = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Cerámica de los martes', 'status' => 'EN_CURSO']);
    \App\Models\WorkshopSession::query()->create(['workshop_id' => $ws->id, 'date' => '2026-11-10', 'start_time' => '18:00', 'end_time' => '20:00', 'status' => 'PROGRAMADA']);
    Event::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Peña de noviembre', 'date' => '2026-11-14', 'time' => '21:00', 'status' => Event::STATUS_PROGRAMADO, 'created_by' => $this->admin->id]);

    $this->actingAs($this->admin)->get(route('agenda.index', ['vista' => 'mes', 'desde' => '2026-11-01']))->assertOk()
        ->assertSee('agenda-grid', false)->assertSee('Cerámica de los')->assertSee('Peña de noviembre')
        ->assertSee(route('events.create', ['date' => '2026-11-10']), false);
    $this->actingAs($this->admin)->get(route('events.create', ['date' => '2026-11-10']))->assertOk()->assertSee('2026-11-10', false);
});

it('sin la agenda contratada, el calendario de eventos sigue funcionando solo', function () {
    $restaurante = communityOrg(false, 'RESTAURANTE');
    $admin = orgUser($restaurante);
    app(\App\Services\ModuleLicenseService::class)->save($restaurante, ['events' => true, 'workshops' => false]);

    $this->actingAs($admin)->get(route('events.index'))->assertOk()->assertSee('Calendario de Eventos');
});

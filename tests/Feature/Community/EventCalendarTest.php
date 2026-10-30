<?php

use App\Models\Workshop;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('el calendario de eventos muestra las clases de los talleres y cada día permite crear un evento', function () {
    config(['app.demo_entry' => false]);
    $org = communityOrg();
    $admin = orgUser($org);
    $ws = Workshop::query()->create(['restaurant_id' => $org->id, 'name' => 'Cerámica de los martes', 'status' => 'EN_CURSO']);
    \App\Models\WorkshopSession::query()->create(['workshop_id' => $ws->id, 'date' => '2026-11-10', 'start_time' => '18:00', 'end_time' => '20:00', 'status' => 'PROGRAMADA']);

    $this->actingAs($admin)->get(route('events.index', ['year' => 2026, 'month' => 11]))->assertOk()
        ->assertSee('event-class', false)->assertSee('Cerámica de los')
        ->assertSee(route('events.create', ['date' => '2026-11-10']), false);
    $this->actingAs($admin)->get(route('events.create', ['date' => '2026-11-10']))->assertOk()->assertSee('2026-11-10', false);
});

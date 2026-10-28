<?php

use App\Models\Event;
use App\Models\Workshop;
use App\Services\Community\MembershipService;
use App\Support\DemoCommunityData;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('la entrada «Centro cultural» habilita sólo los módulos de gestión y deja ejemplos para crear actividades', function () {
    config(['app.demo_entry' => true]);
    $org = communityOrg(false, 'RESTAURANTE');
    $admin = orgUser($org);

    $this->actingAs($admin)->get(route('entry.choose'))->assertOk()->assertSee('Centro cultural');
    $this->post(route('entry.culture'))->assertRedirect(route('agenda.index'));
    $this->post(route('entry.culture'))->assertRedirect(route('agenda.index')); // no duplica los ejemplos

    expect(Workshop::query()->where('name', DemoCommunityData::MARKER)->count())->toBe(1);
    $ws = Workshop::query()->where('name', DemoCommunityData::MARKER)->first();
    expect($ws->enrollments()->where('status', 'EN_ESPERA')->count())->toBe(1)
        ->and($ws->sessions()->count())->toBeGreaterThan(5);

    $this->get(route('agenda.index'))->assertOk()->assertSee('Nuevo taller')->assertSee('Nuevo evento')->assertSee('Reunión de coordinación');
    $this->get(route('workshops.create'))->assertOk();
    $this->post(route('workshops.store'), ['name' => 'Teatro comunitario', 'status' => 'ABIERTO', 'capacity' => 10,
        'schedules' => [['weekday' => 3, 'start_time' => '19:00', 'end_time' => '21:00']]])->assertRedirect();
    expect(Workshop::query()->where('name', 'Teatro comunitario')->first()->schedules()->count())->toBe(1);
    $this->get(route('market.consignments.index'))->assertOk()->assertSee('Huerta del Sur');
    // La venta de comida queda fuera en esta visita.
    $this->get(route('tables.index'))->assertRedirect(route('dashboard'));
});

it('configurar módulos permite elegir también los de gestión cultural', function () {
    config(['app.demo_entry' => true]);
    $admin = orgUser(communityOrg(false, 'RESTAURANTE'));

    $this->actingAs($admin)->get(route('entry.modules'))->assertOk()->assertSee('Centros culturales, escuelas y mercados')->assertSee('Talleres y agenda');
    $this->post(route('entry.modules.store'), ['modules' => ['tables' => 1, 'workshops' => 1]])->assertRedirect();

    $this->get(route('workshops.index'))->assertOk();
    $this->get(route('tables.index'))->assertOk();
    $this->get(route('contacts.index'))->assertRedirect(route('dashboard'));
});

it('sin entrada de demo no se cargan ejemplos', function () {
    config(['app.demo_entry' => false]);
    $admin = orgUser(communityOrg());
    DemoCommunityData::ensure($admin);
    expect(Workshop::withoutGlobalScopes()->count())->toBe(0);
});

it('la coordinación (por membresía) crea eventos y les arma etapas', function () {
    config(['app.demo_entry' => false]);
    $org = communityOrg();
    $coord = orgUser(communityOrg(), 'MOZO', ['username' => 'coord']);
    app(MembershipService::class)->add($org->id, 'coord', 'COORDINADOR', orgUser($org));

    $this->actingAs($coord)->post(route('branch.store'), ['restaurant_id' => $org->id]);
    $this->get(route('events.create'))->assertOk();
    $event = Event::query()->create(['restaurant_id' => $org->id, 'created_by' => $coord->id, 'name' => 'Feria', 'date' => today()->addWeek(), 'status' => 'PROGRAMADO']);
    $this->get(route('events.show', $event))->assertOk();
    $this->post(route('events.stages.store', $event), ['name' => 'Armado', 'status' => 'PENDIENTE', 'budget' => '1000'])->assertSessionHasNoErrors();
    expect($event->stages()->count())->toBe(1);
});

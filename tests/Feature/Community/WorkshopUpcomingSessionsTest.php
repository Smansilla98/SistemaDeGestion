<?php

use App\Models\Workshop;
use App\Models\WorkshopSession;
use Illuminate\Support\Carbon;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-03 10:00'); // martes
    config(['app.demo_entry' => false]);
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
});

afterEach(fn () => Carbon::setTestNow());

$sabados = ['schedules' => [['weekday' => 6, 'start_time' => '17:00', 'end_time' => '20:00']]];

it('al crear un taller abierto con horario se crean solas las clases de las próximas semanas', function () use ($sabados) {
    $this->actingAs($this->admin)->post(route('workshops.store'), ['name' => 'Fotografía', 'status' => 'ABIERTO'] + $sabados)
        ->assertSessionHasNoErrors()->assertSessionHas('success', fn ($m) => str_contains($m, 'Se crearon 8 clases'));

    $ws = Workshop::query()->where('name', 'Fotografía')->firstOrFail();
    expect(WorkshopSession::query()->where('workshop_id', $ws->id)->count())->toBe(8)
        ->and(WorkshopSession::query()->where('workshop_id', $ws->id)->orderBy('date')->value('date')->toDateString())->toBe('2026-11-07');
});

it('una propuesta no crea clases hasta que abre la inscripción', function () use ($sabados) {
    $this->actingAs($this->admin)->post(route('workshops.store'), ['name' => 'Cerámica', 'status' => 'PROPUESTA'] + $sabados)
        ->assertSessionHas('success', fn ($m) => str_contains($m, 'se crean solas'));
    $ws = Workshop::query()->where('name', 'Cerámica')->firstOrFail();
    expect(WorkshopSession::query()->where('workshop_id', $ws->id)->count())->toBe(0);

    $this->actingAs($this->admin)->put(route('workshops.update', $ws), ['name' => 'Cerámica', 'status' => 'ABIERTO'] + $sabados)->assertSessionHasNoErrors();
    expect(WorkshopSession::query()->where('workshop_id', $ws->id)->count())->toBe(8);
});

it('al cambiar el horario se quitan las clases futuras del viejo y se respetan las suspendidas', function () use ($sabados) {
    $this->actingAs($this->admin)->post(route('workshops.store'), ['name' => 'Danza', 'status' => 'EN_CURSO'] + $sabados);
    $ws = Workshop::query()->where('name', 'Danza')->firstOrFail();
    $suspendida = WorkshopSession::query()->where('workshop_id', $ws->id)->orderBy('date')->firstOrFail();
    $suspendida->update(['status' => WorkshopSession::CANCELADA]);

    $this->actingAs($this->admin)->put(route('workshops.update', $ws), ['name' => 'Danza', 'status' => 'EN_CURSO',
        'schedules' => [['weekday' => 3, 'start_time' => '19:00', 'end_time' => '21:00']]])->assertSessionHasNoErrors();

    $sesiones = WorkshopSession::query()->where('workshop_id', $ws->id)->get();
    expect($sesiones->where('status', WorkshopSession::PROGRAMADA)->every(fn ($s) => $s->date->dayOfWeekIso === 3))->toBeTrue()
        ->and($sesiones->where('status', WorkshopSession::PROGRAMADA)->count())->toBe(8)
        ->and($sesiones->firstWhere('id', $suspendida->id))->not->toBeNull();
});

it('el comando diario completa las semanas que faltan y no duplica', function () use ($sabados) {
    $this->actingAs($this->admin)->post(route('workshops.store'), ['name' => 'Murga', 'status' => 'ABIERTO'] + $sabados);
    $ws = Workshop::query()->where('name', 'Murga')->firstOrFail();

    Carbon::setTestNow('2026-11-17 06:00');
    $this->artisan('workshops:sessions')->assertSuccessful();
    $this->artisan('workshops:sessions')->assertSuccessful();

    // 8 semanas desde el 17/11 llegan al 12/01: 7/11 y 14/11 ya pasaron, hay clases del 7/11 al 9/1.
    expect(WorkshopSession::query()->where('workshop_id', $ws->id)->count())->toBe(10);
});

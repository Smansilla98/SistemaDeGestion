<?php

use App\Models\Contact;
use App\Models\User;
use App\Models\Workshop;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
});

$signup = fn (array $extra = []) => array_merge([
    'name' => 'Marta Ríos', 'username' => 'marta', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
    'role' => 'PROFESOR', 'phone' => '1155550000',
], $extra);

it('el registro ofrece todos los roles menos administrador', function () {
    $this->get(route('register'))->assertOk()
        ->assertSee('Profesor')->assertSee('Alumno')->assertSee('Coordinador')->assertSee('Mozo')
        ->assertDontSee('value="ADMIN"', false)->assertDontSee('value="SUPERADMIN"', false);
});

it('un profe se registra, queda activo, ligado a su contacto y avisa a la administración', function () use ($signup) {
    $this->post(route('register'), $signup())->assertSessionHasNoErrors()->assertRedirect(route('login'))->assertSessionHas('success', 'Cuenta creada. Ya podés iniciar sesión.');

    $user = User::query()->where('username', 'marta')->firstOrFail();
    expect($user->role)->toBe('PROFESOR')->and((bool) $user->is_active)->toBeTrue()->and($user->restaurant_id)->toBe($this->org->id)
        ->and(Contact::query()->where('user_id', $user->id)->value('kind'))->toBe('PROFESOR')
        ->and(notificationsTitled($this->admin, 'Nueva cuenta: Marta'))->toBe(1);

    $this->post(route('login'), ['username' => 'marta', 'password' => 'secreto123'])->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

it('un rol del equipo queda pendiente hasta que la administración lo aprueba', function () use ($signup) {
    $this->post(route('register'), $signup(['username' => 'caja', 'role' => 'CAJERO']))
        ->assertSessionHas('success', fn ($m) => str_contains($m, 'aprobarla'));

    $user = User::query()->where('username', 'caja')->firstOrFail();
    expect((bool) $user->is_active)->toBeFalse()->and(notificationsTitled($this->admin, 'Cuenta para aprobar'))->toBe(1);

    $this->post(route('login'), ['username' => 'caja', 'password' => 'secreto123'])->assertSessionHasErrors('username');
    $this->assertGuest();
});

it('un emprendedor que se registra queda ligado a su ficha y ve sus productos', function () use ($signup) {
    $this->post(route('register'), $signup(['username' => 'huerta', 'role' => 'EMPRENDEDOR', 'business_name' => 'Huerta del Sur']))->assertSessionHasNoErrors();

    $user = User::query()->where('username', 'huerta')->firstOrFail();
    $ent = \App\Models\Entrepreneur::query()->where('user_id', $user->id)->firstOrFail();
    expect($ent->business_name)->toBe('Huerta del Sur')->and($ent->restaurant_id)->toBe($this->org->id)
        ->and($ent->contact_id)->toBe(Contact::query()->where('user_id', $user->id)->value('id'));

    \App\Models\MarketProduct::query()->create(['restaurant_id' => $this->org->id, 'entrepreneur_id' => $ent->id, 'name' => 'Acelga', 'unit' => 'atado', 'price_cents' => 100000]);
    \App\Models\MarketProduct::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Producto ajeno', 'unit' => 'u', 'price_cents' => 100]);
    $this->actingAs($user)->get(route('market.products.index'))->assertOk()->assertSee('Acelga')->assertDontSee('Producto ajeno');
});

it('no se puede elegir administrador al registrarse', function () use ($signup) {
    foreach (['ADMIN', 'SUPERADMIN'] as $role) {
        $this->post(route('register'), $signup(['username' => 'x'.$role, 'role' => $role]))->assertSessionHasErrors('role');
    }
    expect(User::query()->whereIn('username', ['xADMIN', 'xSUPERADMIN'])->exists())->toBeFalse();
});

it('el inicio muestra primero la agenda y lo cultural; la barra queda plegada al final', function () {
    $workshop = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Percusión', 'status' => 'ABIERTO']);
    \App\Models\WorkshopSession::query()->create(['workshop_id' => $workshop->id, 'date' => now()->addDays(2)->toDateString(),
        'start_time' => '18:00', 'end_time' => '20:00', 'status' => 'PROGRAMADA']);
    \App\Models\Event::query()->create(['restaurant_id' => $this->org->id, 'created_by' => $this->admin->id, 'name' => 'Peña del sábado',
        'date' => now()->toDateString(), 'time' => '21:00', 'status' => 'PROGRAMADO']);

    $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()
        ->assertSee('Hoy en Patuka')
        ->assertSeeInOrder(['Agenda de la semana', 'Hoy', 'Peña del sábado', 'Percusión', 'Talleres activos', 'Barra'])
        ->getContent();

    // La barra va plegada (details sin open) para quien gestiona el espacio.
    expect($html)->toMatch('/<details class="bar-summary[^"]*"\s*>/');

    // Menú: primero lo cultural, la barra después.
    $this->get(route('dashboard'))->assertSeeInOrder(['Espacio cultural', 'Comunidad', 'Feria', 'Barra', 'Administración']);
});

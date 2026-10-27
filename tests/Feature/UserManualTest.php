<?php

use App\Http\Middleware\ResolveActiveRestaurant;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\UserManual;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create(['is_active' => true]);
});

it('cada rol abre su propio manual', function (string $role, string $expected) {
    $user = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => $role]);

    $this->actingAs($user)->withSession(['entry_mode' => 'demo', ResolveActiveRestaurant::SESSION_KEY => $this->restaurant->id])
        ->get(route('manual'))
        ->assertOk()
        ->assertSee('Sos '.UserManual::roles()[$role]['label'])
        ->assertSee($expected)
        ->assertSee('Ir a la pantalla');
})->with([
    ['MOZO', 'Tomar un pedido'],
    ['COCINA', 'La pantalla de cocina'],
    ['CAJERO', 'Cerrar la caja'],
    ['ENCARGADO', 'Puesta a punto'],
    ['SUPERVISOR', 'Ver el salón'],
    ['GERENTE', 'Medios de cobro'],
    ['ADMIN', 'Sectores y barras'],
    ['SUPERADMIN', 'Monitor de módulos'],
]);

it('se puede leer el manual de otro rol, sin atajos a pantallas ajenas', function () {
    $mozo = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => 'MOZO']);

    $this->actingAs($mozo)->withSession(['entry_mode' => 'demo'])
        ->get(route('manual', 'admin'))
        ->assertOk()
        ->assertSee('Rol: Administrador')
        ->assertDontSee('Ir a la pantalla');
});

it('un rol desconocido cae en un manual válido', function () {
    $mozo = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => 'MOZO']);

    $this->actingAs($mozo)->withSession(['entry_mode' => 'demo'])
        ->get(route('manual', 'astronauta'))
        ->assertOk()
        ->assertSee('Sos Mozo');
});

it('todas las secciones que usa cada rol existen y sus pantallas también', function () {
    $sections = UserManual::sections();
    foreach (UserManual::roles() as $role => $meta) {
        foreach ($meta['sections'] as $key) {
            expect($sections)->toHaveKey($key);
            if (isset($sections[$key]['route'])) {
                expect(\Illuminate\Support\Facades\Route::has($sections[$key]['route']))->toBeTrue("Ruta {$sections[$key]['route']} ({$role})");
            }
        }
    }
});

it('el menú lateral muestra el manual a todos', function () {
    $cajero = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => 'CAJERO']);

    $this->actingAs($cajero)->withSession(['entry_mode' => 'demo'])
        ->get(route('manual'))
        ->assertSee('Manual de uso');
});

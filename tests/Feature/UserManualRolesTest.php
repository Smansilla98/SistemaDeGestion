<?php

use App\Support\UserManual;
use Illuminate\Support\Facades\Route;

require_once __DIR__.'/Community/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('cada rol asignable tiene su manual y no cae en el de mozo', function () {
    foreach (\App\Models\User::getAssignableRoles() as $role) {
        expect(UserManual::normalizeRole($role))->toBe($role, "Falta el manual de {$role}");
    }
});

it('todas las secciones existen y sus pantallas también', function () {
    $library = UserManual::sections();
    foreach (UserManual::roles() as $role => $def) {
        foreach ($def['sections'] as $key) {
            expect(array_key_exists($key, $library))->toBeTrue("{$role} usa la sección inexistente {$key}");
            if (isset($library[$key]['route'])) {
                expect(Route::has($library[$key]['route']))->toBeTrue("La sección {$key} apunta a una ruta que no existe");
            }
        }
    }
});

it('una profe ve su manual con la asistencia', function () {
    config(['app.demo_entry' => false]);
    $org = communityOrg();
    $this->actingAs(orgUser($org, 'PROFESOR'))->get(route('manual'))->assertOk()
        ->assertSee('Tomar asistencia')->assertDontSee('Tomar pedido');
});

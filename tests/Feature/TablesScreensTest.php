<?php

use App\Models\Restaurant;
use App\Models\Sector;
use App\Models\Table;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create(['is_active' => true]);
    $this->sector = Sector::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => 'Salón', 'type' => 'SECTOR', 'parent_id' => null]);
    $this->table = Table::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'sector_id' => $this->sector->id,
        'number' => '7',
        'status' => Table::STATUS_LIBRE,
        'capacity' => 4,
    ]);
    $this->as = function (string $role) {
        $user = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => $role]);

        return $this->actingAs($user)->withSession(['entry_mode' => 'demo']);
    };
});

it('mozo y admin ven la mesa con «Tomar pedido» y el modal de pedido', function (string $role) {
    ($this->as)($role)->get(route('tables.index'))
        ->assertOk()
        ->assertSee('Tomar pedido')
        ->assertSee('id="newOrderModal"', false)
        ->assertSee('id="changeStatusModal"', false);
})->with(['MOZO', 'ADMIN', 'GERENTE', 'ENCARGADO']);

it('el cajero ve el salón pero no toma pedidos', function () {
    ($this->as)('CAJERO')->get(route('tables.index'))
        ->assertOk()
        ->assertSee('Ocupar mesa')
        ->assertDontSee('id="newOrderModal"', false);
});

it('el plano abre el primer sector si no se elige ninguno', function () {
    ($this->as)('ADMIN')->get(route('tables.layout'))
        ->assertOk()
        ->assertSee('Plano del salón')
        ->assertSee('data-kind="table"', false)
        ->assertSee('Editar plano');
});

it('el mozo usa el plano pero no lo edita', function () {
    ($this->as)('MOZO')->get(route('tables.layout', $this->sector->id))
        ->assertOk()
        ->assertDontSee('id="plEditToggle"', false);

    ($this->as)('MOZO')->postJson(route('tables.update-layout'), [
        'sector_id' => $this->sector->id,
        'tables' => [['id' => $this->table->id, 'position_x' => 10, 'position_y' => 10]],
    ])->assertForbidden();
});

it('guardar el plano fija la posición de las mesas', function () {
    ($this->as)('ADMIN')->postJson(route('tables.update-layout'), [
        'sector_id' => $this->sector->id,
        'tables' => [['id' => $this->table->id, 'position_x' => 168, 'position_y' => 240]],
    ])->assertOk()->assertJsonPath('success', true);

    expect($this->table->fresh()->only(['position_x', 'position_y']))->toBe(['position_x' => 168, 'position_y' => 240]);
});

it('cambiar el estado vuelve a la pantalla desde la que se hizo', function () {
    $from = '/tables/layout/'.$this->sector->id;

    ($this->as)('ADMIN')->put(route('tables.update-status', $this->table), [
        'status' => 'RESERVADA', 'guests_count' => 2, 'return_to' => $from,
    ])->assertRedirect($from);

    expect($this->table->fresh()->status)->toBe('RESERVADA');
});

it('no redirige a sitios externos al cambiar el estado', function (string $returnTo) {
    ($this->as)('ADMIN')->put(route('tables.update-status', $this->table), [
        'status' => 'RESERVADA', 'guests_count' => 2, 'return_to' => $returnTo,
    ])->assertRedirect(route('tables.index'));
})->with(['https://evil.example/tables', '//evil.example', '/tablesfalsas']);

it('el mozo recibe el aviso de pedidos listos', function () {
    ($this->as)('MOZO')->getJson('/api/notifications/ready-orders')
        ->assertOk()
        ->assertJsonPath('success', true);
});

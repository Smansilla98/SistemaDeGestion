<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Sector;
use App\Models\Table;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create(['is_active' => true]);
    $this->cocina = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => 'COCINA']);
    $table = Table::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'sector_id' => Sector::factory()->create(['restaurant_id' => $this->restaurant->id])->id,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'category_id' => Category::factory()->create(['restaurant_id' => $this->restaurant->id])->id,
    ]);
    $this->order = Order::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'table_id' => $table->id,
        'user_id' => $this->cocina->id,
        'status' => 'ENVIADO',
    ]);
    $this->items = collect([1, 2])->map(fn () => OrderItem::create([
        'order_id' => $this->order->id,
        'product_id' => $product->id,
        'product_name_snapshot' => $product->name,
        'quantity' => 1,
        'unit_price' => 100,
        'subtotal' => 100,
        'status' => 'PENDIENTE',
    ]));
    $this->kds = fn () => $this->actingAs($this->cocina)->withSession(['entry_mode' => 'demo']);
});

it('la pantalla de cocina trae los botones para mover los pedidos', function () {
    ($this->kds)()->get(route('kitchen.index'))
        ->assertOk()
        ->assertSee('orders\\/__ID__\\/status', false)
        ->assertSee('Entregado');
});

it('cocina lleva un pedido de nuevo a entregado', function () {
    foreach (['EN_PREPARACION', 'LISTO', 'ENTREGADO'] as $status) {
        ($this->kds)()->putJson(route('kitchen.update-order-status', $this->order), ['status' => $status])
            ->assertOk()->assertJsonPath('success', true);
        expect($this->order->fresh()->status)->toBe($status);
    }
});

it('un cambio que ya no corresponde responde 422 y no rompe', function () {
    $this->order->update(['status' => 'ENTREGADO']);

    ($this->kds)()->putJson(route('kitchen.update-order-status', $this->order), ['status' => 'LISTO'])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('tildar los platos mueve el pedido solo', function () {
    [$a, $b] = $this->items->all();

    ($this->kds)()->postJson(route('kitchen.update-item-status', $a), ['status' => 'LISTO'])->assertOk();
    expect($this->order->fresh()->status)->toBe('EN_PREPARACION');

    ($this->kds)()->postJson(route('kitchen.update-item-status', $b), ['status' => 'LISTO'])->assertOk();
    expect($this->order->fresh()->status)->toBe('LISTO');

    ($this->kds)()->postJson(route('kitchen.update-item-status', $b), ['status' => 'EN_PREPARACION'])->assertOk();
    expect($this->order->fresh()->status)->toBe('EN_PREPARACION');
});

it('volver a preparación destilda los platos', function () {
    $this->items->each->update(['status' => 'LISTO']);
    $this->order->update(['status' => 'LISTO']);

    ($this->kds)()->putJson(route('kitchen.update-order-status', $this->order), ['status' => 'EN_PREPARACION'])->assertOk();

    expect($this->order->items()->pluck('status')->unique()->all())->toBe(['EN_PREPARACION']);
});

it('el mozo no cambia estados desde cocina', function () {
    $mozo = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => 'MOZO']);

    $this->actingAs($mozo)->withSession(['entry_mode' => 'demo'])
        ->putJson(route('kitchen.update-order-status', $this->order), ['status' => 'LISTO'])
        ->assertForbidden();
});

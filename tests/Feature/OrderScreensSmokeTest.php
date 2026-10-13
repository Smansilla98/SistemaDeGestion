<?php

use App\Livewire\Mobile\TomaPedido;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\Restaurant;
use App\Models\Sector;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create(['is_active' => true]);
    $this->admin = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => 'ADMIN']);
    $this->category = Category::factory()->create(['restaurant_id' => $this->restaurant->id]);
    $this->table = Table::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'sector_id' => Sector::factory()->create(['restaurant_id' => $this->restaurant->id])->id,
    ]);

    $this->pizza = Product::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'category_id' => $this->category->id,
        'name' => 'Pizza',
        'price' => 18000,
        'type' => 'PRODUCT',
    ]);
    $this->napolitana = $this->pizza->modifiers()->create([
        'name' => 'Napolitana', 'kind' => ProductModifier::KIND_VARIANT, 'price' => 20000, 'price_modifier' => 0,
    ]);

    $this->actingAs($this->admin)->withSession(['entry_mode' => 'demo']);
});

it('las pantallas de toma de pedido cargan el selector de variantes', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('AlToqueVariants', false)
        ->assertSee('Napolitana', false);
})->with(['orders.create', 'orders.quick.index', 'tables.index']);

it('el detalle del pedido permite reemplazar por una variante', function () {
    $order = Order::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'table_id' => $this->table->id,
        'user_id' => $this->admin->id,
    ]);

    $this->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('replaceNewVariantId', false);
});

it('la pantalla de sucursales lista el grupo y crea una sucursal', function () {
    $this->get(route('branches.index'))->assertOk()->assertSee($this->restaurant->name);

    $this->post(route('branches.create'), ['name' => 'Sucursal Centro', 'copy_catalog' => '1'])
        ->assertRedirect(route('branches.index'));

    $branch = Restaurant::where('name', 'Sucursal Centro')->first();
    expect($branch->parent_id)->toBe($this->restaurant->id)
        ->and(Product::withoutGlobalScopes()->where('restaurant_id', $branch->id)->where('name', 'Pizza')->exists())->toBeTrue();
});

it('toma de pedido mobile: exige variante y la manda al pedido', function () {
    Livewire::test(TomaPedido::class)
        ->call('selectMesa', $this->table->id)
        ->call('selectProducto', $this->pizza->id)
        ->assertSet('precioActual', null)
        ->call('agregarItem')
        ->assertSet('items', [])
        ->call('selectVariante', $this->napolitana->id)
        ->assertSet('precioActual', 20000.0)
        ->call('agregarItem')
        ->assertCount('items', 1)
        ->call('confirmarPedido');

    $item = Order::where('table_id', $this->table->id)->firstOrFail()->items()->first();
    expect((float) $item->unit_price)->toBe(20000.0)
        ->and($item->product_name_snapshot)->toBe('Pizza · Napolitana');
});

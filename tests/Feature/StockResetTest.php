<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockReset;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\User;
use App\Services\OrderService;
use App\Services\StockResetService;
use Illuminate\Support\Facades\Hash;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create(['is_active' => true]);
    $this->category = Category::factory()->create(['restaurant_id' => $this->restaurant->id]);
    $this->admin = User::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'username' => 'reset_admin',
        'password' => Hash::make('password123'),
        'role' => 'ADMIN',
    ]);
    $this->table = Table::factory()->create(['restaurant_id' => $this->restaurant->id]);

    $this->product = Product::factory()->withStock()->create([
        'restaurant_id' => $this->restaurant->id,
        'category_id' => $this->category->id,
        'name' => 'Empanada',
        'price' => 1000,
    ]);
    Stock::create(['restaurant_id' => $this->restaurant->id, 'product_id' => $this->product->id, 'quantity' => 50]);

    $this->actingAs($this->admin);
    $this->order = app(OrderService::class)->createOrder([
        'restaurant_id' => $this->restaurant->id,
        'table_id' => $this->table->id,
        'user_id' => $this->admin->id,
        'items' => [['product_id' => $this->product->id, 'quantity' => 4]],
    ]);
});

it('frena los pedidos, libera las mesas y fija el stock real', function () {
    expect($this->table->fresh()->status)->toBe(Table::STATUS_OCUPADA);

    $reset = app(StockResetService::class)->execute(
        $this->restaurant->id,
        $this->admin,
        [$this->product->id => 37],
        'Apertura'
    );

    $table = $this->table->fresh();

    expect($this->order->fresh()->status)->toBe(Order::STATUS_CANCELADO)
        ->and($table->status)->toBe(Table::STATUS_LIBRE)
        ->and($table->current_order_id)->toBeNull()
        ->and($table->current_session_id)->toBeNull()
        ->and(TableSession::where('restaurant_id', $this->restaurant->id)->where('status', TableSession::STATUS_ABIERTA)->count())->toBe(0)
        ->and((int) Stock::where('product_id', $this->product->id)->value('quantity'))->toBe(37)
        ->and($reset->orders_cancelled)->toBe(1)
        ->and($reset->orders_cancelled_total_cents)->toBe(400000)
        ->and($reset->tables_freed)->toBe(1)
        ->and($reset->products_adjusted)->toBe(1);

    $item = $reset->items()->first();
    expect($item->previous_quantity)->toBe(46)->and($item->counted_quantity)->toBe(37);

    $movement = StockMovement::where('reference', "stock_reset_{$reset->id}")->first();
    expect($movement->type)->toBe('AJUSTE')
        ->and($movement->previous_stock)->toBe(46)
        ->and($movement->new_stock)->toBe(37);
});

it('no toca otros restaurantes', function () {
    $other = Restaurant::factory()->create();
    $otherTable = Table::factory()->create(['restaurant_id' => $other->id, 'status' => Table::STATUS_OCUPADA]);
    $otherOrder = Order::factory()->create(['restaurant_id' => $other->id, 'table_id' => $otherTable->id, 'status' => 'ENVIADO']);

    app(StockResetService::class)->execute($this->restaurant->id, $this->admin, []);

    expect($otherOrder->fresh()->status)->toBe('ENVIADO')
        ->and($otherTable->fresh()->status)->toBe(Table::STATUS_OCUPADA);
});

it('rechaza contar productos de otro local y no deja nada a medias', function () {
    $foreign = Product::factory()->withStock()->create();

    expect(fn () => app(StockResetService::class)->execute($this->restaurant->id, $this->admin, [$foreign->id => 5]))
        ->toThrow(InvalidArgumentException::class);

    expect($this->order->fresh()->status)->not->toBe(Order::STATUS_CANCELADO)
        ->and(StockReset::count())->toBe(0);
});

it('web: exige escribir la confirmación', function () {
    $this->withSession(['entry_mode' => 'demo'])
        ->from(route('stock.reset.index'))
        ->post(route('stock.reset.store'), ['counts' => [$this->product->id => 10], 'confirmation' => 'si'])
        ->assertSessionHasErrors('confirmation');

    expect($this->order->fresh()->status)->not->toBe(Order::STATUS_CANCELADO);

    $this->withSession(['entry_mode' => 'demo'])
        ->post(route('stock.reset.store'), ['counts' => [$this->product->id => 10], 'confirmation' => 'PUESTA A PUNTO'])
        ->assertRedirect(route('stock.reset.index'));

    expect((int) Stock::where('product_id', $this->product->id)->value('quantity'))->toBe(10);
});

it('web: muestra el conteo y lo que se va a bajar', function () {
    $this->withSession(['entry_mode' => 'demo'])
        ->get(route('stock.reset.index'))
        ->assertOk()
        ->assertSee('Empanada')
        ->assertSee('Pedidos en curso');
});

it('web: un mozo no puede hacer la puesta a punto', function () {
    $mozo = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => 'MOZO']);

    $this->actingAs($mozo)->get(route('stock.reset.index'))->assertForbidden();
});

it('api: preview y ejecución con permiso stock.reset', function () {
    $token = $this->postJson('/api/auth/login', ['username' => 'reset_admin', 'password' => 'password123'])->json('data.access_token');
    $headers = ['Authorization' => 'Bearer '.$token];

    $this->withHeaders($headers)->getJson('/api/stock/reset')
        ->assertOk()
        ->assertJsonPath('data.active_orders', 1)
        ->assertJsonPath('data.products.0.name', 'Empanada');

    $this->withHeaders($headers)->postJson('/api/stock/reset', [
        'counts' => [['product_id' => $this->product->id, 'quantity' => 20]],
        'confirmation' => 'nop',
    ])->assertStatus(422);

    $this->withHeaders($headers)->postJson('/api/stock/reset', [
        'counts' => [['product_id' => $this->product->id, 'quantity' => 20]],
        'confirmation' => 'puesta a punto',
    ])->assertCreated()->assertJsonPath('data.orders_cancelled', 1);

    expect((int) Stock::where('product_id', $this->product->id)->value('quantity'))->toBe(20);
});

it('api: un mozo no tiene el permiso', function () {
    User::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'username' => 'reset_mozo',
        'password' => Hash::make('password123'),
        'role' => 'MOZO',
    ]);
    $token = $this->postJson('/api/auth/login', ['username' => 'reset_mozo', 'password' => 'password123'])->json('data.access_token');

    $this->withHeaders(['Authorization' => 'Bearer '.$token])->getJson('/api/stock/reset')->assertForbidden();
});

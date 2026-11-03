<?php

use App\Models\CycleOrder;
use App\Models\MarketProduct;
use App\Models\OrderCycle;
use App\Services\Market\OrderCycleService;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
    config(['app.brand.public_org_id' => $this->org->id]);
    $this->miel = MarketProduct::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Miel', 'unit' => 'frasco', 'price_cents' => 500000]);
    $this->cycle = OrderCycle::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Bolsones de noviembre', 'opens_at' => now()->subDay(),
        'closes_at' => now()->addDays(2), 'delivery_on' => today()->addDays(3), 'status' => 'ABIERTO']);
    app(OrderCycleService::class)->saveOffer($this->cycle, [$this->miel->id => ['max' => 3]]);
});

$datos = fn (array $extra = []) => $extra + ['name' => 'Vecina Pérez', 'phone' => '1155550001', 'items' => [0 => 0]];

it('la portada muestra el ciclo abierto y el vecino hace su pedido sin cuenta', function () use ($datos) {
    $this->get('/')->assertOk()->assertSee('Pedidos abiertos')->assertSee(route('public.nodo', $this->cycle->id), false);
    $this->get(route('public.nodo', $this->cycle->id))->assertOk()->assertSee('Miel')->assertSee('quedan 3');

    $this->post(route('public.nodo.order', $this->cycle->id), $datos(['items' => [$this->miel->id => 2]]))
        ->assertRedirect(route('public.nodo', $this->cycle->id))->assertSessionHas('ordered', fn ($m) => str_contains($m, '$10.000'));

    $order = CycleOrder::query()->where('order_cycle_id', $this->cycle->id)->firstOrFail();
    expect($order->total_cents)->toBe(1000000)->and($order->contact->phone)->toBe('1155550001')
        ->and(notificationsTitled($this->admin, 'Pedido nuevo en el nodo'))->toBe(1);
});

it('desde la web no se pisa un pedido existente ni se pasa del cupo', function () use ($datos) {
    $this->post(route('public.nodo.order', $this->cycle->id), $datos(['items' => [$this->miel->id => 4]]))->assertSessionHasErrors('items');
    $this->post(route('public.nodo.order', $this->cycle->id), $datos(['items' => [$this->miel->id => 1]]))->assertSessionHasNoErrors();
    $this->post(route('public.nodo.order', $this->cycle->id), $datos(['name' => 'Otra persona', 'items' => [$this->miel->id => 2]]))
        ->assertSessionHasErrors('phone');

    expect(CycleOrder::query()->where('order_cycle_id', $this->cycle->id)->first()->total_cents)->toBe(500000);
});

it('un ciclo cerrado o sin oferta no se ve en la web', function () {
    $this->cycle->update(['status' => 'CERRADO']);
    $this->get(route('public.nodo', $this->cycle->id))->assertNotFound();
    $this->get('/')->assertOk()->assertDontSee('Pedidos abiertos');

    $sinOferta = OrderCycle::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Vacío', 'opens_at' => now()->subDay(),
        'closes_at' => now()->addDay(), 'delivery_on' => today()->addDays(2), 'status' => 'ABIERTO']);
    $this->get(route('public.nodo', $sinOferta->id))->assertNotFound();
});

it('la gestión ve el link para compartir', function () {
    config(['app.demo_entry' => false]);
    $this->actingAs($this->admin)->get(route('market.cycles.show', $this->cycle))->assertOk()
        ->assertSee(route('public.nodo', $this->cycle->id))->assertSee('Compartir');
});

<?php

use App\Models\Consignment;
use App\Models\Contact;
use App\Models\Entrepreneur;
use App\Models\MarketProduct;
use App\Models\MarketStockMovement;
use App\Models\OrderCycle;
use App\Models\User;
use App\Services\Market\ConsignmentService;
use App\Services\Market\MarketStockService;
use App\Services\Market\OrderCycleService;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    $this->org = communityOrg(true, 'NODO_AGROECOLOGICO');
    $this->admin = orgUser($this->org);
    $this->actingAs($this->admin);
    $this->ownerUser = orgUser(communityOrg(), 'MOZO', ['username' => 'huerta']);
    app(\App\Services\Community\MembershipService::class)->add($this->org->id, 'huerta', User::ROLE_EMPRENDEDOR, $this->admin);
    $this->ent = Entrepreneur::query()->create(['restaurant_id' => $this->org->id, 'business_name' => 'La Huerta', 'user_id' => $this->ownerUser->id, 'payment_alias' => 'huerta.mp']);
    $this->miel = MarketProduct::query()->create(['restaurant_id' => $this->org->id, 'entrepreneur_id' => $this->ent->id, 'name' => 'Miel', 'unit' => 'frasco', 'price_cents' => 500000, 'min_stock' => 2]);
});

it('consignación: stock, ventas FIFO, devolución, comisión y liquidación separan stock de dinero', function () {
    $svc = app(ConsignmentService::class);
    $stock = app(MarketStockService::class);
    $c1 = $svc->receive($this->ent, today()->subDays(10)->toDateString(), 30, [['market_product_id' => $this->miel->id, 'quantity' => 5]], $this->admin);
    $c2 = $svc->receive($this->ent, today()->toDateString(), 30, [['market_product_id' => $this->miel->id, 'quantity' => 4, 'unit_price_cents' => 550000]], $this->admin);
    expect($this->miel->fresh()->stock_quantity)->toBe(9.0);

    $stock->sell($this->miel, 6, $this->admin); // 5 de la primera + 1 de la segunda
    $stock->returnToEntrepreneur($c2->items()->first(), 1, $this->admin);

    $b1 = $svc->balance($c1);
    $b2 = $svc->balance($c2->fresh());
    expect($b1['sold'])->toBe(5.0)->and($b1['gross_cents'])->toBe(2500000)->and($b1['commission_cents'])->toBe(750000)->and($b1['owed_cents'])->toBe(1750000)
        ->and($b2['sold'])->toBe(1.0)->and($b2['returned'])->toBe(1.0)->and($b2['pending_qty'])->toBe(2.0)
        ->and($this->miel->fresh()->stock_quantity)->toBe(2.0);
    expect(MarketStockMovement::query()->where('market_product_id', $this->miel->id)->orderByDesc('id')->value('balance_after'))->toEqual(2.0);

    $s = $svc->settle($c1, today()->toDateString(), 'transferencia', null, $this->admin);
    expect($s->paid_cents)->toBe(1750000)->and($c1->fresh()->status)->toBe('CERRADA')->and($svc->balance($c1)['owed_cents'])->toBe(0);
    expect(notificationsTitled($this->ownerUser, 'Liquidación'))->toBe(1);

    expect(fn () => $svc->settle($c1, today()->toDateString(), null, null, $this->admin))->toThrow(ValidationException::class);
    // Alerta de stock bajo al cruzar el mínimo.
    expect(notificationsTitled($this->admin, 'Stock bajo: Miel'))->toBe(1);
});

it('nunca deja stock negativo y revierte toda la operación', function () {
    app(MarketStockService::class)->move($this->miel, 'INGRESO', 1, $this->admin);
    expect(fn () => app(MarketStockService::class)->sell($this->miel, 2, $this->admin))->toThrow(ValidationException::class);
    expect($this->miel->fresh()->stock_quantity)->toBe(1.0)->and(MarketStockMovement::query()->count())->toBe(1);

    $this->post(route('market.products.move', $this->miel), ['type' => 'AJUSTE_MENOS', 'quantity' => 5])->assertSessionHasErrors('quantity');
});

it('ciclo de pedidos: consolida, entrega descontando stock y bloquea si falta mercadería', function () {
    $cycle = OrderCycle::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Bolsones', 'opens_at' => now()->subDay(), 'closes_at' => now()->addDay(), 'delivery_on' => today()->addDays(2), 'status' => 'ABIERTO']);
    $ana = Contact::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Ana', 'kind' => 'CLIENTE']);
    $svc = app(OrderCycleService::class);
    $svc->saveOffer($cycle, [$this->miel->id => []]);

    $order = $svc->placeOrder($cycle, $ana, [$this->miel->id => 2]);
    $svc->placeOrder($cycle, $ana, [$this->miel->id => 3]); // reemplaza
    expect($order->fresh()->total_cents)->toBe(1500000)->and($svc->consolidated($cycle)->first()['quantity'])->toBe(3.0);

    expect(fn () => $svc->deliver($order->fresh(), $this->admin))->toThrow(ValidationException::class);
    expect($order->fresh()->status)->toBe('PENDIENTE');

    app(ConsignmentService::class)->receive($this->ent, today()->toDateString(), 20, [['market_product_id' => $this->miel->id, 'quantity' => 3]], $this->admin);
    $this->post(route('market.cycles.orders.deliver', [$cycle, $order]), ['paid' => '15.000'])->assertSessionHasNoErrors();
    expect($order->fresh()->status)->toBe('ENTREGADO')->and($order->fresh()->paid_cents)->toBe(1500000)
        ->and($this->miel->fresh()->stock_quantity)->toBe(0.0)
        ->and(app(ConsignmentService::class)->balance(Consignment::query()->first())['sold'])->toBe(3.0);

    $cycle->update(['status' => 'CERRADO']);
    expect(fn () => $svc->placeOrder($cycle->fresh(), $ana, [$this->miel->id => 1]))->toThrow(ValidationException::class);
});

it('nodo: sólo se pide lo que el ciclo ofrece, con su precio y cupo, y la oferta se copia al ciclo siguiente', function () {
    $dulce = MarketProduct::query()->create(['restaurant_id' => $this->org->id, 'entrepreneur_id' => $this->ent->id, 'name' => 'Dulce', 'unit' => 'frasco', 'price_cents' => 300000]);
    $cycle = OrderCycle::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Bolsones 1', 'opens_at' => now()->subDay(), 'closes_at' => now()->addDay(), 'delivery_on' => today()->addDays(2), 'status' => 'ABIERTO']);
    $ana = Contact::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Ana', 'kind' => 'CLIENTE']);
    $beto = Contact::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Beto', 'kind' => 'CLIENTE']);
    $svc = app(OrderCycleService::class);

    expect(fn () => $svc->placeOrder($cycle, $ana, [$this->miel->id => 1]))->toThrow(ValidationException::class);

    $this->post(route('market.cycles.offer', $cycle), ['offer' => [$this->miel->id => 1], 'price' => [$this->miel->id => '4.500,00'], 'max' => [$this->miel->id => 3]])
        ->assertSessionHasNoErrors();
    expect($cycle->offer()->count())->toBe(1)->and($cycle->offer()->first()->price_cents)->toBe(450000);

    expect(fn () => $svc->placeOrder($cycle, $ana, [$dulce->id => 1]))->toThrow(ValidationException::class);
    $order = $svc->placeOrder($cycle, $ana, [$this->miel->id => 2]);
    expect($order->total_cents)->toBe(900000);
    expect(fn () => $svc->placeOrder($cycle, $beto, [$this->miel->id => 2]))->toThrow(ValidationException::class);
    $svc->placeOrder($cycle, $ana, [$this->miel->id => 3]); // reemplaza su pedido: no se cuenta dos veces

    expect(fn () => $svc->saveOffer($cycle, [$dulce->id => []]))->toThrow(ValidationException::class);

    $this->post(route('market.cycles.store'), ['name' => 'Bolsones 2', 'opens_at' => now()->toDateTimeString(), 'closes_at' => now()->addDays(3)->toDateTimeString(),
        'delivery_on' => today()->addDays(4)->toDateString(), 'copy_from' => $cycle->id])->assertSessionHasNoErrors();
    $next = OrderCycle::query()->where('name', 'Bolsones 2')->firstOrFail();
    expect($next->offer()->first()->price_cents)->toBe(450000)->and($next->offer()->first()->max_quantity)->toBe(3.0);
    $this->get(route('market.cycles.show', $next))->assertOk()->assertSee('Productos del ciclo')->assertSee('Quedan 3');
});

it('el emprendedor sólo ve lo suyo', function () {
    $otro = Entrepreneur::query()->create(['restaurant_id' => $this->org->id, 'business_name' => 'Otro']);
    MarketProduct::query()->create(['restaurant_id' => $this->org->id, 'entrepreneur_id' => $otro->id, 'name' => 'Dulce ajeno', 'unit' => 'frasco', 'price_cents' => 100]);

    $this->actingAs($this->ownerUser)->post(route('branch.store'), ['restaurant_id' => $this->org->id]);
    $this->get(route('market.products.index'))->assertOk()->assertSee('Miel')->assertDontSee('Dulce ajeno');
    $this->get(route('market.entrepreneurs.show', $otro))->assertForbidden();
    $this->get(route('market.entrepreneurs.show', $this->ent))->assertOk();
    $this->post(route('market.products.move', $this->miel), ['type' => 'INGRESO', 'quantity' => 1])->assertForbidden();
});

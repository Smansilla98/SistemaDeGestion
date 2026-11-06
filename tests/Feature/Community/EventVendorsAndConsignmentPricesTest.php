<?php

use App\Models\ActivityEntry;
use App\Models\Entrepreneur;
use App\Models\Event;
use App\Models\EventVendor;
use App\Models\MarketProduct;
use App\Services\Market\ConsignmentService;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
    $this->actingAs($this->admin);
    $this->ent = Entrepreneur::query()->create(['restaurant_id' => $this->org->id, 'business_name' => 'Pega Pega', 'payment_alias' => 'pega.mp']);
});

it('consignación con su precio y el mío: marco lo vendido, le debo su precio y me quedo la diferencia', function () {
    $sticker = MarketProduct::query()->create(['restaurant_id' => $this->org->id, 'entrepreneur_id' => $this->ent->id, 'name' => 'Pegatina',
        'unit' => 'unidad', 'price_cents' => 150000, 'entrepreneur_price_cents' => 100000]);

    $this->post(route('market.consignments.store'), ['entrepreneur_id' => $this->ent->id, 'received_on' => today()->toDateString(), 'commission_percent' => 0,
        'items' => [['market_product_id' => $sticker->id, 'quantity' => 10, 'price' => '', 'sale_price' => '']]])->assertRedirect();
    $consignment = $this->ent->consignments()->sole();
    $item = $consignment->items()->sole();
    expect($item->unit_price_cents)->toBe(100000)->and($item->salePriceCents())->toBe(150000);

    $this->get(route('market.consignments.show', $consignment))->assertOk()->assertSee('Su precio')->assertSee('Mi precio')->assertSee('Vendí 1');

    foreach (range(1, 3) as $_) {
        $this->post(route('market.consignments.sell', [$consignment, $item]), ['quantity' => 1])->assertSessionHas('success');
    }
    $this->post(route('market.consignments.sell', [$consignment, $item]), ['quantity' => -1])->assertSessionHas('success'); // me equivoqué

    $b = app(ConsignmentService::class)->balance($consignment->fresh());
    expect($b['sold'])->toBe(2.0)
        ->and($b['gross_cents'])->toBe(300000)       // cobré 2 × $1.500
        ->and($b['owed_cents'])->toBe(200000)        // le debo 2 × $1.000
        ->and($b['commission_cents'])->toBe(100000)  // me quedan $1.000
        ->and($sticker->fresh()->stock_quantity)->toBe(8.0);

    $this->post(route('market.consignments.sell', [$consignment, $item]), ['quantity' => 9])->assertSessionHasErrors('quantity');
});

it('la consignación con porcentaje sigue igual: mismo precio y comisión', function () {
    $miel = MarketProduct::query()->create(['restaurant_id' => $this->org->id, 'entrepreneur_id' => $this->ent->id, 'name' => 'Miel', 'unit' => 'frasco', 'price_cents' => 500000]);
    $c = app(ConsignmentService::class)->receive($this->ent, today()->toDateString(), 30, [['market_product_id' => $miel->id, 'quantity' => 2]], $this->admin);
    app(\App\Services\Market\MarketStockService::class)->sell($miel, 2, $this->admin);

    $b = app(ConsignmentService::class)->balance($c);
    expect($b['gross_cents'])->toBe(1000000)->and($b['commission_cents'])->toBe(300000)->and($b['owed_cents'])->toBe(700000);
});

it('puestos del evento: la casa vende bebidas y los emprendedores de comida dejan su porcentaje sin stock', function () {
    $event = Event::query()->create(['restaurant_id' => $this->org->id, 'created_by' => $this->admin->id, 'name' => 'Peña', 'date' => today(), 'status' => Event::STATUS_PROGRAMADO]);
    $comida = Entrepreneur::query()->create(['restaurant_id' => $this->org->id, 'business_name' => 'Comidas típicas']);

    $this->put(route('events.house-sells', $event), ['house_sells' => ['BEBIDA']])->assertSessionHas('success');
    $this->post(route('events.vendors.store', $event), ['entrepreneur_id' => $comida->id, 'category' => 'COMIDA', 'percent' => 15])->assertSessionHas('success');
    $this->post(route('events.vendors.store', $event), ['name' => 'Parrillero Juan', 'category' => 'PARRILLA', 'percent' => 20])->assertSessionHas('success');
    $this->post(route('events.vendors.store', $event), ['category' => 'COMIDA', 'percent' => 10])->assertSessionHasErrors('name');

    $puesto = EventVendor::query()->where('name', 'Comidas típicas')->sole();
    $this->post(route('events.vendors.collect', [$event, $puesto]))->assertSessionHasErrors('sales');

    $this->put(route('events.vendors.update', [$event, $puesto]), ['entrepreneur_id' => $comida->id, 'name' => 'Comidas típicas', 'category' => 'COMIDA', 'percent' => 15, 'sales' => '200.000'])
        ->assertSessionHas('success');
    expect($puesto->fresh()->commissionCents())->toBe(3000000);

    $this->get(route('events.show', $event))->assertOk()->assertSee('Quién vende qué')->assertSee('Parrillero Juan')->assertSee('$30.000');

    $this->post(route('events.vendors.collect', [$event, $puesto]))->assertSessionHas('success');
    expect($puesto->fresh()->collected_cents)->toBe(3000000)->and($puesto->fresh()->pendingCents())->toBe(0);
    $entry = ActivityEntry::query()->where('entryable_id', $event->id)->sole();
    expect($entry->type)->toBe('INGRESO')->and($entry->amount_cents)->toBe(3000000)->and($entry->concept)->toContain('Comidas típicas');

    $this->post(route('events.vendors.collect', [$event, $puesto]))->assertSessionHasErrors('sales');
    $this->delete(route('events.vendors.destroy', [$event, $puesto]))->assertSessionHas('error');
    expect($event->fresh()->house_sells)->toBe(['BEBIDA']);

    $this->get(route('market.entrepreneurs.show', $comida))->assertOk()->assertSee('Puestos en eventos')->assertSee('Peña');
});

it('sin permiso de eventos no se cargan puestos', function () {
    $event = Event::query()->create(['restaurant_id' => $this->org->id, 'created_by' => $this->admin->id, 'name' => 'Peña', 'date' => today(), 'status' => Event::STATUS_PROGRAMADO]);
    $this->actingAs(orgUser($this->org, 'MOZO'))->post(route('events.vendors.store', $event), ['name' => 'X', 'category' => 'COMIDA', 'percent' => 10])->assertForbidden();
});

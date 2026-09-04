<?php

use App\Domain\Numbering\SequenceGenerator;
use App\Domain\Orders\Actions\CreateOrder;
use App\Domain\Orders\CreateOrderData;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransition;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Str;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('formatea números ORD-año-sec bajo secuencia', function () {
    $restaurant = Restaurant::factory()->create();

    $n1 = app(SequenceGenerator::class)->formatted($restaurant->id, 'ORD', (int) date('Y'));
    $n2 = app(SequenceGenerator::class)->formatted($restaurant->id, 'ORD', (int) date('Y'));

    expect($n1)->toBeOrderNumber()
        ->and($n2)->toBeOrderNumber()
        ->and($n1)->not->toBe($n2);
});

it('CreateOrder es idempotente con la misma key', function () {
    $restaurant = Restaurant::factory()->create();
    $user = User::factory()->create(['restaurant_id' => $restaurant->id]);
    $key = (string) Str::ulid();

    $data = new CreateOrderData(
        restaurantId: $restaurant->id,
        userId: $user->id,
        idempotencyKey: $key,
        customerName: 'Barra',
    );

    $a = app(CreateOrder::class)->handle($data);
    $b = app(CreateOrder::class)->handle($data);

    expect($a->id)->toBe($b->id)
        ->and(Order::withoutGlobalScopes()->where('restaurant_id', $restaurant->id)->count())->toBe(1);
});

it('no permite reabrir un pedido cerrado', function () {
    $restaurant = Restaurant::factory()->create();
    $user = User::factory()->create(['restaurant_id' => $restaurant->id]);
    $order = Order::withoutGlobalScopes()->create([
        'restaurant_id' => $restaurant->id,
        'user_id' => $user->id,
        'number' => 'ORD-'.date('Y').'-0099',
        'status' => OrderStatus::CERRADO->value,
    ]);

    $order->transitionTo(OrderStatus::ABIERTO, $user);
})->throws(InvalidOrderTransition::class);

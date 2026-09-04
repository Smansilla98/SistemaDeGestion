<?php

namespace Tests\Unit;

use App\Domain\Numbering\SequenceGenerator;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransition;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequence_recovers_from_existing_orders(): void
    {
        $restaurant = Restaurant::factory()->create();
        $user = User::factory()->create(['restaurant_id' => $restaurant->id]);
        $year = (int) date('Y');

        Order::withoutGlobalScopes()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'number' => 'ORD-'.$year.'-1000',
            'status' => OrderStatus::ABIERTO->value,
            'customer_name' => 'Barra',
        ]);

        $order = app(OrderService::class)->createOrder([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'customer_name' => 'Barra 2',
        ]);

        $this->assertSame('ORD-'.$year.'-1001', $order->number);
    }

    public function test_idempotency_key_returns_same_order(): void
    {
        $restaurant = Restaurant::factory()->create();
        $user = User::factory()->create(['restaurant_id' => $restaurant->id]);
        $key = (string) Str::ulid();

        $a = app(OrderService::class)->createOrder([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'customer_name' => 'Idem',
            'idempotency_key' => $key,
        ]);
        $b = app(OrderService::class)->createOrder([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'customer_name' => 'Idem',
            'idempotency_key' => $key,
        ]);

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Order::withoutGlobalScopes()->where('restaurant_id', $restaurant->id)->count());
    }

    public function test_cannot_reopen_closed_order(): void
    {
        $restaurant = Restaurant::factory()->create();
        $user = User::factory()->create(['restaurant_id' => $restaurant->id]);
        $order = Order::withoutGlobalScopes()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'number' => 'ORD-'.date('Y').'-0001',
            'status' => OrderStatus::CERRADO->value,
        ]);

        $this->expectException(InvalidOrderTransition::class);
        $order->transitionTo(OrderStatus::ABIERTO, $user);
    }

    public function test_two_restaurants_can_share_formatted_number(): void
    {
        $year = (int) date('Y');
        $a = Restaurant::factory()->create();
        $b = Restaurant::factory()->create();
        $ua = User::factory()->create(['restaurant_id' => $a->id]);
        $ub = User::factory()->create(['restaurant_id' => $b->id]);

        $oa = app(OrderService::class)->createOrder([
            'restaurant_id' => $a->id,
            'user_id' => $ua->id,
            'customer_name' => 'A',
        ]);
        $ob = app(OrderService::class)->createOrder([
            'restaurant_id' => $b->id,
            'user_id' => $ub->id,
            'customer_name' => 'B',
        ]);

        $this->assertSame('ORD-'.$year.'-0001', $oa->number);
        $this->assertSame('ORD-'.$year.'-0001', $ob->number);
    }
}

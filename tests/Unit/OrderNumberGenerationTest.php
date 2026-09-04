<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderNumberGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_order_number_recovers_from_desynced_counter(): void
    {
        $restaurant = Restaurant::factory()->create();
        $user = User::factory()->create(['restaurant_id' => $restaurant->id]);
        $year = (int) date('Y');

        // Pedido ya existente (como si el contador se hubiera quedado atrás).
        Order::create([
            'restaurant_id' => $restaurant->id,
            'table_id' => null,
            'user_id' => $user->id,
            'number' => 'ORD-'.$year.'-1000',
            'status' => 'ABIERTO',
            'customer_name' => 'Pedido en la barra',
        ]);

        // Contador desfasado: cree que el próximo es 1000.
        DB::table('order_counters')->insert([
            'restaurant_id' => $restaurant->id,
            'year' => $year,
            'last_seq' => 999,
        ]);

        $order = app(OrderService::class)->createOrder([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'customer_name' => 'Pedido en la barra',
        ]);

        $this->assertSame('ORD-'.$year.'-1001', $order->number);
        $this->assertSame(1001, (int) DB::table('order_counters')
            ->where('restaurant_id', $restaurant->id)
            ->where('year', $year)
            ->value('last_seq'));
    }

    public function test_two_restaurants_can_share_same_order_number_format(): void
    {
        $year = (int) date('Y');
        $a = Restaurant::factory()->create();
        $b = Restaurant::factory()->create();
        $userA = User::factory()->create(['restaurant_id' => $a->id]);
        $userB = User::factory()->create(['restaurant_id' => $b->id]);

        $orderA = app(OrderService::class)->createOrder([
            'restaurant_id' => $a->id,
            'user_id' => $userA->id,
            'customer_name' => 'Local A',
        ]);
        $orderB = app(OrderService::class)->createOrder([
            'restaurant_id' => $b->id,
            'user_id' => $userB->id,
            'customer_name' => 'Local B',
        ]);

        $this->assertSame('ORD-'.$year.'-0001', $orderA->number);
        $this->assertSame('ORD-'.$year.'-0001', $orderB->number);
    }
}

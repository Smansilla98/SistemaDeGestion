<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $table = Table::factory()->create();

        return [
            'restaurant_id' => $table->restaurant_id,
            'table_id' => $table->id,
            'user_id' => User::factory()->create(['restaurant_id' => $table->restaurant_id])->id,
            'number' => 'ORD-'.fake()->unique()->numberBetween(1000, 999999),
            'status' => Order::STATUS_ABIERTO,
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
        ];
    }
}

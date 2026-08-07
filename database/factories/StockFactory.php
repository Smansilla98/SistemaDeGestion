<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        $product = Product::factory()->withStock()->create();

        return [
            'restaurant_id' => $product->restaurant_id,
            'product_id' => $product->id,
            'quantity' => fake()->numberBetween(10, 200),
            'minimum_stock' => 5,
        ];
    }
}

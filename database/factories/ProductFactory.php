<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $restaurant = Restaurant::factory()->create();

        return [
            'restaurant_id' => $restaurant->id,
            'category_id' => Category::factory()->create(['restaurant_id' => $restaurant->id])->id,
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 100, 5000),
            'has_stock' => false,
            'stock_minimum' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Producto que controla stock.
     */
    public function withStock(int $minimum = 5): static
    {
        return $this->state(fn () => [
            'has_stock' => true,
            'stock_minimum' => $minimum,
        ]);
    }
}

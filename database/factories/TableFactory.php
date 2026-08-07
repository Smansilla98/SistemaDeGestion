<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Sector;
use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Table>
 */
class TableFactory extends Factory
{
    protected $model = Table::class;

    public function definition(): array
    {
        $restaurant = Restaurant::factory()->create();

        return [
            'restaurant_id' => $restaurant->id,
            'sector_id' => Sector::factory()->create(['restaurant_id' => $restaurant->id])->id,
            'number' => (string) fake()->unique()->numberBetween(1, 9999),
            'capacity' => fake()->numberBetween(2, 8),
            'status' => Table::STATUS_LIBRE,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Table>
 */
class TableFactory extends Factory
{
    protected $model = Table::class;

    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'number' => (string) $this->faker->unique()->numberBetween(1, 99),
            'capacity' => 4,
            'status' => Table::STATUS_LIBRE,
        ];
    }
}

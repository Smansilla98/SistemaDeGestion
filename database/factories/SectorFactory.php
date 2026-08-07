<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sector>
 */
class SectorFactory extends Factory
{
    protected $model = Sector::class;

    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'parent_id' => null,
            'name' => 'Sector '.fake()->unique()->numberBetween(1, 9999),
            'type' => Sector::TYPE_SECTOR,
            'capacity' => fake()->numberBetween(10, 60),
            'is_active' => true,
        ];
    }
}

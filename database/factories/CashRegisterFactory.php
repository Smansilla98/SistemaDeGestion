<?php

namespace Database\Factories;

use App\Models\CashRegister;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashRegister>
 */
class CashRegisterFactory extends Factory
{
    protected $model = CashRegister::class;

    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => 'Caja '.$this->faker->unique()->numberBetween(1, 9),
            'is_active' => true,
        ];
    }
}

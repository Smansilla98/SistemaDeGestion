<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    use App\Models\Product;

    public function run(): void
    {
        Product::create([
            'name' => 'Hamburguesa',
            'price' => 2500,
        ]);

        Product::create([
            'name' => 'Papas fritas',
            'price' => 1500,
        ]);
    }

}

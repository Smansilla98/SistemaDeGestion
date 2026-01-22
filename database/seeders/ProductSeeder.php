<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
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

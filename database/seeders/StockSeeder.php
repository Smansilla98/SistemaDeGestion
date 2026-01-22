<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
   use App\Models\Stock;

public function run(): void
{
    Stock::create([
        'restaurant_id' => 1,
        'product_id' => 1,
        'quantity' => 5,
        'minimum_stock' => 10,
    ]);

    Stock::create([
        'restaurant_id' => 1,
        'product_id' => 2,
        'quantity' => 20,
        'minimum_stock' => 5,
    ]);
}

}

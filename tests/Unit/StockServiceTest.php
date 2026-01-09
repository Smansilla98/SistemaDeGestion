<?php

namespace Tests\Unit;

use App\Models\Stock;
use App\Models\Product;
use App\Models\Category;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = app(StockService::class);
    }

    /**
     * Test: Reducir stock actualiza cantidad correctamente
     */
    public function test_reduce_stock_updates_quantity()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'restaurant_id' => 1,
        ]);

        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'restaurant_id' => 1,
            'quantity' => 100,
            'minimum_stock' => 10,
        ]);

        $this->stockService->reduceStock($stock, 20);

        $stock->refresh();

        $this->assertEquals(80, $stock->quantity);
    }

    /**
     * Test: Verificar alerta de stock bajo
     */
    public function test_low_stock_alert()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'restaurant_id' => 1,
        ]);

        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'restaurant_id' => 1,
            'quantity' => 5,
            'minimum_stock' => 10,
        ]);

        $this->assertTrue($stock->quantity < $stock->minimum_stock);
        $this->assertTrue($this->stockService->isLowStock($stock));
    }
}


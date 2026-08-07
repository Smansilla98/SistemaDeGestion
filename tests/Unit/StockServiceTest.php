<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $stockService;

    protected Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = app(StockService::class);
        $this->restaurant = Restaurant::factory()->create();
    }

    /**
     * Crear un producto con stock asociado al restaurante del test.
     */
    protected function crearStock(int $quantity, int $minimum): Stock
    {
        $category = Category::factory()->create([
            'restaurant_id' => $this->restaurant->id,
        ]);

        $product = Product::factory()->withStock($minimum)->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $category->id,
        ]);

        return Stock::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'minimum_stock' => $minimum,
        ]);
    }

    /**
     * Test: Reducir stock actualiza cantidad correctamente
     */
    public function test_reduce_stock_updates_quantity()
    {
        $stock = $this->crearStock(quantity: 100, minimum: 10);

        $this->stockService->reduceStock($stock, 20);

        $stock->refresh();

        $this->assertEquals(80, $stock->quantity);
    }

    /**
     * Test: No se permite dejar el stock en negativo
     */
    public function test_reduce_stock_rejects_negative_result()
    {
        $stock = $this->crearStock(quantity: 5, minimum: 1);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No se puede tener stock negativo');

        $this->stockService->reduceStock($stock, 10);
    }

    /**
     * Test: Verificar alerta de stock bajo
     */
    public function test_low_stock_alert()
    {
        $stock = $this->crearStock(quantity: 5, minimum: 10);

        $this->assertTrue($stock->quantity < $stock->minimum_stock);
        $this->assertTrue($this->stockService->isLowStock($stock));
    }

    /**
     * Test: Un stock por encima del minimo no dispara alerta
     */
    public function test_stock_above_minimum_is_not_low()
    {
        $stock = $this->crearStock(quantity: 50, minimum: 10);

        $this->assertFalse($this->stockService->isLowStock($stock));
    }
}

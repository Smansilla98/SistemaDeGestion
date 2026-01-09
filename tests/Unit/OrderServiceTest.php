<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Table;
use App\Models\Product;
use App\Models\Category;
use App\Services\OrderService;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }

    /**
     * Test: Crear pedido calcula totales correctamente
     */
    public function test_create_order_calculates_totals_correctly()
    {
        $table = Table::factory()->create();
        $category = Category::factory()->create(['restaurant_id' => $table->restaurant_id]);
        
        $product1 = Product::factory()->create([
            'restaurant_id' => $table->restaurant_id,
            'category_id' => $category->id,
            'price' => 100.00,
        ]);

        $product2 = Product::factory()->create([
            'restaurant_id' => $table->restaurant_id,
            'category_id' => $category->id,
            'price' => 50.00,
        ]);

        $orderData = [
            'restaurant_id' => $table->restaurant_id,
            'table_id' => $table->id,
            'user_id' => 1,
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 1],
            ],
        ];

        $order = $this->orderService->createOrder($orderData);

        foreach ($orderData['items'] as $itemData) {
            $this->orderService->addItem($order, $itemData);
        }

        $order->refresh();

        // Subtotal esperado: (100 * 2) + (50 * 1) = 250
        $this->assertEquals(250.00, $order->subtotal);
        $this->assertEquals(250.00, $order->total);
        $this->assertEquals(OrderStatus::ABIERTO, $order->status);
    }

    /**
     * Test: Cerrar pedido actualiza estado correctamente
     */
    public function test_close_order_updates_status()
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::LISTO,
        ]);

        $this->orderService->closeOrder($order);

        $order->refresh();

        $this->assertEquals(OrderStatus::CERRADO, $order->status);
        $this->assertNotNull($order->closed_at);
    }
}


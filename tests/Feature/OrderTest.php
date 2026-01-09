<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Table;
use App\Models\Product;
use App\Models\Category;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario de prueba
        $this->user = User::factory()->create([
            'role' => UserRole::MOZO,
            'restaurant_id' => 1,
        ]);
    }

    /**
     * Test: Usuario autenticado puede ver lista de pedidos
     */
    public function test_authenticated_user_can_view_orders_list()
    {
        $response = $this->actingAs($this->user)
            ->get('/orders');

        $response->assertStatus(200);
        $response->assertViewIs('orders.index');
    }

    /**
     * Test: Usuario puede crear un pedido
     */
    public function test_user_can_create_order()
    {
        $table = Table::factory()->create([
            'restaurant_id' => $this->user->restaurant_id,
        ]);

        $category = Category::factory()->create([
            'restaurant_id' => $this->user->restaurant_id,
        ]);

        $product = Product::factory()->create([
            'restaurant_id' => $this->user->restaurant_id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/orders', [
                'table_id' => $table->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'table_id' => $table->id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test: Usuario no puede crear pedido sin items
     */
    public function test_user_cannot_create_order_without_items()
    {
        $table = Table::factory()->create([
            'restaurant_id' => $this->user->restaurant_id,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/orders', [
                'table_id' => $table->id,
                'items' => [],
            ]);

        $response->assertSessionHasErrors(['items']);
    }
}


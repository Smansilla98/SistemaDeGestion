<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\Sector;
use App\Models\Product;
use App\Models\Category;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->restaurant = Restaurant::factory()->create();
        $this->user = User::factory()->create([
            'role' => UserRole::MOZO,
            'restaurant_id' => $this->restaurant->id,
            'username' => 'mozotest',
        ]);
    }

    /**
     * Test: Usuario autenticado puede ver lista de pedidos
     */
    public function test_authenticated_user_can_view_orders_list(): void
    {
        $response = $this->actingAs($this->user)->get('/orders');

        $response->assertStatus(200);
        $response->assertViewIs('orders.index');
    }

    /**
     * Test: Usuario puede crear un pedido (con modelos creados a mano)
     */
    public function test_user_can_create_order(): void
    {
        $sector = Sector::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Salón',
            'type' => 'sector',
            'parent_id' => null,
        ]);
        $table = Table::create([
            'restaurant_id' => $this->restaurant->id,
            'sector_id' => $sector->id,
            'number' => '1',
            'capacity' => 4,
            'status' => 'OCUPADA',
        ]);
        $category = Category::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Bebidas',
            'is_active' => true,
        ]);
        $product = Product::create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $category->id,
            'name' => 'Agua',
            'price' => 10.00,
            'type' => 'PRODUCT',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post('/orders', [
            'table_id' => $table->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
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
    public function test_user_cannot_create_order_without_items(): void
    {
        $sector = Sector::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Salón',
            'type' => 'sector',
            'parent_id' => null,
        ]);
        $table = Table::create([
            'restaurant_id' => $this->restaurant->id,
            'sector_id' => $sector->id,
            'number' => '2',
            'capacity' => 4,
            'status' => 'OCUPADA',
        ]);

        $response = $this->actingAs($this->user)->post('/orders', [
            'table_id' => $table->id,
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['items']);
    }
}


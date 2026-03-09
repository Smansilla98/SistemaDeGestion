<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Restaurant;
use App\Models\Product;
use App\Models\Category;
use App\Models\Stock;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->restaurant = Restaurant::factory()->create();
        $this->user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'restaurant_id' => $this->restaurant->id,
            'username' => 'adminstock',
        ]);
    }

    /**
     * Test: Usuario autorizado puede ver la lista de stock
     */
    public function test_authenticated_user_can_view_stock_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('stock.index'));

        $response->assertStatus(200);
        $response->assertViewIs('stock.index');
    }

    /**
     * Test: Registrar movimiento de stock (salida) actualiza el stock
     */
    public function test_stock_movement_updates_quantity(): void
    {
        $category = Category::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Insumos',
            'is_active' => true,
        ]);
        $product = Product::create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $category->id,
            'name' => 'Producto con stock',
            'price' => 5.00,
            'type' => 'PRODUCT',
            'is_active' => true,
            'has_stock' => true,
            'stock_minimum' => 2,
        ]);
        Stock::create([
            'restaurant_id' => $this->restaurant->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->user)->post(route('stock.store-movement'), [
            'product_id' => $product->id,
            'type' => 'SALIDA',
            'quantity' => 3,
            'reason' => 'Prueba test',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('stocks', [
            'restaurant_id' => $this->restaurant->id,
            'product_id' => $product->id,
            'quantity' => 7,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'SALIDA',
            'new_stock' => 7,
        ]);
    }
}

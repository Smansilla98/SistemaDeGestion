<?php

namespace Tests\Feature\Orders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Sector;
use App\Models\Stock;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pedido desde el modal de mesas con productos que llevan stock (bebidas).
 * Si falta stock, el mozo tiene que ver qué producto falta, no un "reintentá".
 */
class PedidoMesaStockTest extends TestCase
{
    use RefreshDatabase;

    private User $mozo;

    private Table $mesa;

    private Category $bebidas;

    protected function setUp(): void
    {
        parent::setUp();
        $restaurant = Restaurant::factory()->create();
        $this->mozo = User::factory()->create(['role' => UserRole::MOZO->value, 'restaurant_id' => $restaurant->id, 'username' => 'mozo']);
        $sector = Sector::create(['restaurant_id' => $restaurant->id, 'name' => 'Salón', 'type' => 'sector', 'parent_id' => null]);
        $this->mesa = Table::create(['restaurant_id' => $restaurant->id, 'sector_id' => $sector->id, 'number' => '4', 'capacity' => 4, 'status' => Table::STATUS_LIBRE]);
        $this->bebidas = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Bebidas', 'is_active' => true]);
    }

    private function bebida(string $nombre, int $stock): Product
    {
        $p = Product::create(['restaurant_id' => $this->mesa->restaurant_id, 'category_id' => $this->bebidas->id, 'name' => $nombre, 'price' => 4500, 'type' => 'PRODUCT', 'is_active' => true, 'has_stock' => true]);
        Stock::create(['restaurant_id' => $this->mesa->restaurant_id, 'product_id' => $p->id, 'quantity' => $stock, 'minimum_stock' => 0]);

        return $p;
    }

    public function test_sin_stock_suficiente_dice_que_producto_falta(): void
    {
        $coca = $this->bebida('Coca Cola', 5);
        $fanta = $this->bebida('Fanta', 10);

        $this->actingAs($this->mozo)->postJson("/tables/{$this->mesa->id}/orders", [
            'send_to_kitchen' => true,
            'items' => [['product_id' => $coca->id, 'quantity' => 8], ['product_id' => $fanta->id, 'quantity' => 2]],
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJson(fn ($json) => $json->where('message', fn ($m) => str_contains($m, 'Coca Cola') && str_contains($m, 'Stock insuficiente'))->etc());

        $this->assertSame(0, Order::count());
        $this->assertSame(5, Stock::where('product_id', $coca->id)->value('quantity'));
    }

    public function test_el_mismo_producto_en_dos_lineas_no_deja_el_stock_negativo(): void
    {
        $coca = $this->bebida('Coca Cola', 6);

        $this->actingAs($this->mozo)->postJson("/tables/{$this->mesa->id}/orders", [
            'items' => [['product_id' => $coca->id, 'quantity' => 4], ['product_id' => $coca->id, 'quantity' => 4]],
        ])->assertStatus(422)
            ->assertJson(fn ($json) => $json->where('message', fn ($m) => str_contains($m, 'Coca Cola'))->etc());

        $this->assertSame(6, Stock::where('product_id', $coca->id)->value('quantity'));
    }

    public function test_con_stock_el_pedido_grande_se_crea(): void
    {
        $coca = $this->bebida('Coca Cola', 50);

        $this->actingAs($this->mozo)->postJson("/tables/{$this->mesa->id}/orders", [
            'send_to_kitchen' => true,
            'items' => [['product_id' => $coca->id, 'quantity' => 8]],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(42, Stock::where('product_id', $coca->id)->value('quantity'));
    }
}

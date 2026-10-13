<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\Restaurant;
use App\Models\Stock;
use App\Models\Table;
use App\Models\User;
use App\Services\OrderService;
use App\Services\RecipeService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create();
    $this->category = Category::factory()->create(['restaurant_id' => $this->restaurant->id]);
    $this->user = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => 'ADMIN']);
    $this->table = Table::factory()->create(['restaurant_id' => $this->restaurant->id]);
    $this->actingAs($this->user);

    $this->insumo = function (string $name, int $stock, string $unit = 'ml') {
        $insumo = Product::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => null,
            'name' => $name,
            'type' => 'INSUMO',
            'unit' => $unit,
            'price' => 0,
        ]);
        Stock::create(['restaurant_id' => $this->restaurant->id, 'product_id' => $insumo->id, 'quantity' => $stock]);

        return $insumo;
    };

    $this->stockOf = fn (Product $p) => (int) Stock::where('restaurant_id', $this->restaurant->id)
        ->where('product_id', $p->id)->value('quantity');

    $this->newOrder = fn (array $items) => app(OrderService::class)->createOrder([
        'restaurant_id' => $this->restaurant->id,
        'table_id' => $this->table->id,
        'user_id' => $this->user->id,
        'items' => $items,
    ]);
});

function pizzaConSabores(object $test): array
{
    $pizza = Product::factory()->withStock()->create([
        'restaurant_id' => $test->restaurant->id,
        'category_id' => $test->category->id,
        'name' => 'Pizza',
        'price' => 18000,
        'type' => 'PRODUCT',
    ]);
    Stock::create(['restaurant_id' => $test->restaurant->id, 'product_id' => $pizza->id, 'quantity' => 10]);

    $napolitana = $pizza->modifiers()->create(['name' => 'Napolitana', 'kind' => ProductModifier::KIND_VARIANT, 'price' => 20000, 'price_modifier' => 0]);
    $jamon = $pizza->modifiers()->create(['name' => 'Jamón y morrones', 'kind' => ProductModifier::KIND_VARIANT, 'price' => 24000, 'price_modifier' => 0]);

    return [$pizza, $napolitana, $jamon];
}

it('la variante define el precio: napolitana 20.000 y jamón y morrones 24.000 son la misma pizza', function () {
    [$pizza, $napolitana, $jamon] = pizzaConSabores($this);

    $order = ($this->newOrder)([
        ['product_id' => $pizza->id, 'variant_id' => $napolitana->id, 'quantity' => 1],
        ['product_id' => $pizza->id, 'variant_id' => $jamon->id, 'quantity' => 2],
    ]);

    $lines = $order->items()->orderBy('id')->get();

    expect((float) $lines[0]->unit_price)->toBe(20000.0)
        ->and($lines[0]->product_name_snapshot)->toBe('Pizza · Napolitana')
        ->and((float) $lines[1]->unit_price)->toBe(24000.0)
        ->and((float) $lines[1]->subtotal)->toBe(48000.0)
        ->and((float) $order->fresh()->total)->toBe(68000.0)
        ->and($lines[1]->modifiers()->pluck('name')->all())->toBe(['Jamón y morrones']);

    // Las tres pizzas salen del mismo stock.
    expect(($this->stockOf)($pizza))->toBe(7);
});

it('un producto con variantes no se puede vender sin elegir una', function () {
    [$pizza] = pizzaConSabores($this);

    expect(fn () => ($this->newOrder)([['product_id' => $pizza->id, 'quantity' => 1]]))
        ->toThrow(RuntimeException::class, "Elegí una variante para 'Pizza'");

    expect(($this->stockOf)($pizza))->toBe(10);
});

it('no acepta la variante de otro producto', function () {
    [$pizza] = pizzaConSabores($this);
    $otro = Product::factory()->create(['restaurant_id' => $this->restaurant->id, 'category_id' => $this->category->id]);
    $ajena = $otro->modifiers()->create(['name' => 'Ajena', 'kind' => ProductModifier::KIND_VARIANT, 'price' => 1, 'price_modifier' => 0]);

    expect(fn () => ($this->newOrder)([['product_id' => $pizza->id, 'variant_id' => $ajena->id, 'quantity' => 1]]))
        ->toThrow(RuntimeException::class);
});

it('la variante descuenta sus insumos y los repone si se quita la línea', function () {
    [$pizza, , $jamon] = pizzaConSabores($this);
    $jamonInsumo = ($this->insumo)('Jamón', 500, 'g');
    $jamon->ingredients()->attach($jamonInsumo->id, ['quantity' => 120]);

    $order = ($this->newOrder)([['product_id' => $pizza->id, 'variant_id' => $jamon->id, 'quantity' => 2]]);

    expect(($this->stockOf)($pizza))->toBe(8)
        ->and(($this->stockOf)($jamonInsumo))->toBe(260);

    app(OrderService::class)->removeOrderItems($order, $order->items()->pluck('id')->all());

    expect(($this->stockOf)($pizza))->toBe(10)
        ->and(($this->stockOf)($jamonInsumo))->toBe(500);
});

it('los extras suman su recargo sobre el precio de la variante', function () {
    [$pizza, $napolitana] = pizzaConSabores($this);
    $extra = $pizza->modifiers()->create(['name' => 'Extra queso', 'kind' => ProductModifier::KIND_EXTRA, 'price_modifier' => 1500]);

    $order = ($this->newOrder)([
        ['product_id' => $pizza->id, 'variant_id' => $napolitana->id, 'modifiers' => [$extra->id], 'quantity' => 2],
    ]);

    expect((float) $order->fresh()->total)->toBe(43000.0);
});

it('trago por proporción: fernet con coca de 500 cc descuenta 150 y 350', function () {
    $fernet = ($this->insumo)('Fernet', 750);
    $coca = ($this->insumo)('Coca Cola', 2250);
    $trago = Product::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'category_id' => $this->category->id,
        'name' => 'Fernet con coca',
        'price' => 6000,
        'type' => 'PRODUCT',
    ]);

    $recipes = app(RecipeService::class);
    $recipes->setServingSize($trago, 500);
    $recipes->addIngredient($trago->fresh(), $fernet, null, 30);
    $recipes->addIngredient($trago->fresh(), $coca, null, 70);

    ($this->newOrder)([['product_id' => $trago->id, 'quantity' => 2]]);

    expect(($this->stockOf)($fernet))->toBe(450)
        ->and(($this->stockOf)($coca))->toBe(1550);
});

it('las proporciones no pueden superar el 100% del vaso', function () {
    $fernet = ($this->insumo)('Fernet', 750);
    $coca = ($this->insumo)('Coca Cola', 2250);
    $trago = Product::factory()->create(['restaurant_id' => $this->restaurant->id, 'category_id' => $this->category->id, 'type' => 'PRODUCT']);

    $recipes = app(RecipeService::class);
    $recipes->setServingSize($trago, 500);
    $recipes->addIngredient($trago->fresh(), $fernet, null, 40);

    expect(fn () => $recipes->addIngredient($trago->fresh(), $coca, null, 70))
        ->toThrow(InvalidArgumentException::class, 'superan el 100%');
});

it('cambiar la medida del vaso recalcula las cantidades por proporción', function () {
    $fernet = ($this->insumo)('Fernet', 750);
    $trago = Product::factory()->create(['restaurant_id' => $this->restaurant->id, 'category_id' => $this->category->id, 'type' => 'PRODUCT']);

    $recipes = app(RecipeService::class);
    $recipes->setServingSize($trago, 500);
    $recipes->addIngredient($trago->fresh(), $fernet, null, 30);
    $recipes->setServingSize($trago->fresh(), 300);

    expect((int) $trago->fresh()->ingredients()->first()->pivot->quantity)->toBe(90);
});

it('no se puede cargar por proporción sin medida del vaso', function () {
    $fernet = ($this->insumo)('Fernet', 750);
    $trago = Product::factory()->create(['restaurant_id' => $this->restaurant->id, 'category_id' => $this->category->id, 'type' => 'PRODUCT']);

    expect(fn () => app(RecipeService::class)->addIngredient($trago, $fernet, null, 30))
        ->toThrow(InvalidArgumentException::class, 'medida del vaso');
});

it('web: el ABM de producto carga variantes y la API las expone', function () {
    [$pizza] = pizzaConSabores($this);

    $this->withSession(['entry_mode' => 'demo'])
        ->post(route('products.variants.store', $pizza), ['name' => 'Fugazzeta', 'kind' => 'VARIANT', 'price' => 22000])
        ->assertRedirect();

    $this->withSession(['entry_mode' => 'demo'])
        ->get(route('products.edit', $pizza))
        ->assertOk()
        ->assertSee('Fugazzeta');

    $variants = app(\App\Services\ProductService::class)->getById($pizza->id, $this->restaurant->id)['variants'];
    expect(collect($variants)->pluck('name')->all())->toContain('Fugazzeta', 'Napolitana');
});

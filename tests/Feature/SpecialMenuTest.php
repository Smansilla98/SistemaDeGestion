<?php

use App\Models\AgendaNotice;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\SpecialMenu;
use App\Models\Stock;
use App\Models\Table;
use App\Models\User;
use App\Services\OrderService;
use App\Services\RecipeService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    $this->restaurant = Restaurant::factory()->create();
    $this->category = Category::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => 'Comidas']);
    $this->user = User::factory()->create(['restaurant_id' => $this->restaurant->id, 'role' => 'ADMIN']);
    $this->table = Table::factory()->create(['restaurant_id' => $this->restaurant->id]);
    $this->actingAs($this->user);
    $product = fn (string $name, int $price) => Product::factory()->create(['restaurant_id' => $this->restaurant->id,
        'category_id' => $this->category->id, 'name' => $name, 'price' => $price, 'type' => 'PRODUCT', 'has_stock' => false, 'is_active' => true]);
    $this->locro = $product('Locro', 9000);
    $this->empanada = $product('Empanada', 1500);
    $this->pizza = $product('Pizza', 12000);
    $this->order = fn (Product $p, int $qty = 1) => app(OrderService::class)->createOrder([
        'restaurant_id' => $this->restaurant->id, 'table_id' => $this->table->id, 'user_id' => $this->user->id,
        'items' => [['product_id' => $p->id, 'quantity' => $qty]],
    ]);
});

it('crea el menú con aviso en la agenda y no deja superponer fechas', function () {
    $this->post(route('special-menus.store'), ['name' => '25 de Mayo', 'starts_on' => '2027-05-24', 'ends_on' => '2027-05-25', 'in_agenda' => 1, 'exclusive' => 1])
        ->assertRedirect();
    $menu = SpecialMenu::query()->firstOrFail();
    expect($menu->exclusive)->toBeTrue()
        ->and(AgendaNotice::query()->where('kind', 'FECHA_ESPECIAL')->value('title'))->toBe('Menú especial: 25 de Mayo');

    $this->post(route('special-menus.store'), ['name' => 'Otro', 'starts_on' => '2027-05-25', 'ends_on' => '2027-05-26'])->assertSessionHasErrors('starts_on');

    $this->put(route('special-menus.update', $menu), ['name' => '25 de Mayo', 'starts_on' => '2027-05-24', 'ends_on' => '2027-05-25'])->assertSessionHasNoErrors();
    expect(AgendaNotice::query()->count())->toBe(0)->and($menu->fresh()->exclusive)->toBeFalse();
});

it('menú exclusivo: sólo se vende lo del menú y al precio del menú', function () {
    $menu = SpecialMenu::query()->create(['restaurant_id' => $this->restaurant->id, 'name' => 'Patrio', 'starts_on' => today(), 'ends_on' => today(), 'exclusive' => true]);
    $this->post(route('special-menus.items', $menu), [
        'include' => [$this->locro->id => 1, $this->empanada->id => 1],
        'price' => [$this->locro->id => '7500', $this->empanada->id => ''],
        'planned' => [$this->locro->id => 40],
    ])->assertSessionHasNoErrors();

    $order = ($this->order)($this->locro, 2);
    expect((float) $order->items()->first()->unit_price)->toBe(7500.0);
    $order2 = ($this->order)($this->empanada, 3);
    expect((float) $order2->items()->first()->unit_price)->toBe(1500.0);
    expect(fn () => ($this->order)($this->pizza))->toThrow(RuntimeException::class);

    $this->get(route('orders.create'))->assertOk()->assertSee('Menú especial: Patrio')->assertSee('Locro')->assertDontSee('Pizza');
});

it('menú no exclusivo: la carta sigue y los del menú usan su precio; fuera de fecha no rige', function () {
    $menu = SpecialMenu::query()->create(['restaurant_id' => $this->restaurant->id, 'name' => 'Show', 'starts_on' => today()->addDay(), 'ends_on' => today()->addDays(2)]);
    $menu->items()->create(['product_id' => $this->pizza->id, 'price' => 10000]);

    expect((float) ($this->order)($this->pizza)->items()->first()->unit_price)->toBe(12000.0);

    $menu->update(['starts_on' => today()]);
    expect((float) ($this->order)($this->pizza)->items()->first()->unit_price)->toBe(10000.0)
        ->and((float) ($this->order)($this->locro)->items()->first()->unit_price)->toBe(9000.0);
});

it('calcula el stock que hace falta para lo previsto con las recetas', function () {
    $zapallo = Product::factory()->create(['restaurant_id' => $this->restaurant->id, 'category_id' => null, 'name' => 'Zapallo', 'type' => 'INSUMO', 'unit' => 'g', 'price' => 0]);
    Stock::create(['restaurant_id' => $this->restaurant->id, 'product_id' => $zapallo->id, 'quantity' => 5000]);
    app(RecipeService::class)->addIngredient($this->locro, $zapallo, 200);

    $menu = SpecialMenu::query()->create(['restaurant_id' => $this->restaurant->id, 'name' => 'Patrio', 'starts_on' => today()->addWeek(), 'ends_on' => today()->addWeek()]);
    $menu->items()->create(['product_id' => $this->locro->id, 'planned_quantity' => 40]);

    $this->get(route('special-menus.show', $menu))->assertOk()
        ->assertSee('Zapallo')->assertSee('8000 g')->assertSee('3000 g')->assertSee('Para comprar');
});

it('no se ve el menú de otra organización', function () {
    $other = Restaurant::factory()->create();
    $menu = SpecialMenu::query()->create(['restaurant_id' => $other->id, 'name' => 'Ajeno', 'starts_on' => today(), 'ends_on' => today()]);
    $this->get(route('special-menus.show', $menu))->assertNotFound();
});

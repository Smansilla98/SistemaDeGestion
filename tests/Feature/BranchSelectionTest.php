<?php

use App\Models\Restaurant;
use App\Models\Table;
use App\Models\User;
use App\Services\BranchService;
use Illuminate\Support\Facades\Hash;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);

    $this->main = Restaurant::factory()->create(['name' => 'Casa Matriz', 'is_active' => true]);
    $this->branch = Restaurant::factory()->create(['name' => 'Sucursal Norte', 'parent_id' => $this->main->id, 'is_active' => true]);
    $this->foreign = Restaurant::factory()->create(['name' => 'Otro negocio', 'is_active' => true]);

    $this->admin = User::factory()->create([
        'restaurant_id' => $this->main->id,
        'username' => 'branch_admin',
        'password' => Hash::make('password123'),
        'role' => 'ADMIN',
        'is_active' => true,
    ]);
    $this->encargado = User::factory()->create([
        'restaurant_id' => $this->branch->id,
        'username' => 'branch_encargado',
        'password' => Hash::make('password123'),
        'role' => 'ENCARGADO',
        'is_active' => true,
    ]);
});

it('web: el admin de un grupo con sucursales elige sucursal al iniciar sesión', function () {
    $this->post('/login', ['username' => 'branch_admin', 'password' => 'password123'])
        ->assertRedirect(route('branch.select'));

    $this->get(route('branch.select'))
        ->assertOk()
        ->assertSee('Casa Matriz')
        ->assertSee('Sucursal Norte')
        ->assertDontSee('Otro negocio');
});

it('web: sin sucursal elegida no se puede operar', function () {
    $this->actingAs($this->admin)
        ->get(route('tables.index'))
        ->assertRedirect(route('branch.select'));
});

it('web: al elegir sucursal el admin opera sobre los datos de esa sucursal', function () {
    foreach ([[$this->main, '901'], [$this->branch, '902']] as [$restaurant, $number]) {
        Table::factory()->create([
            'restaurant_id' => $restaurant->id,
            'sector_id' => \App\Models\Sector::factory()->create(['restaurant_id' => $restaurant->id])->id,
            'number' => $number,
        ]);
    }

    $this->actingAs($this->admin)
        ->post(route('branch.store'), ['restaurant_id' => $this->branch->id])
        ->assertRedirect(route('dashboard'));

    $this->actingAs($this->admin)
        ->get(route('tables.index'))
        ->assertOk()
        ->assertSee('data-table-number="902"', false)
        ->assertDontSee('data-table-number="901"', false);

    // La fila del usuario no cambia: la sucursal activa vive en la sesión.
    expect((int) $this->admin->fresh()->homeRestaurantId())->toBe($this->main->id);
});

it('web: el admin no puede elegir un restaurante fuera de su grupo', function () {
    $this->actingAs($this->admin)
        ->from(route('branch.select'))
        ->post(route('branch.store'), ['restaurant_id' => $this->foreign->id])
        ->assertRedirect(route('branch.select'))
        ->assertSessionHasErrors('restaurant_id');
});

it('web: el encargado entra directo a su local, sin elegir', function () {
    $this->post('/login', ['username' => 'branch_encargado', 'password' => 'password123'])
        ->assertRedirect(route('dashboard'));

    $this->get(route('branch.select'))->assertRedirect(route('dashboard'));
});

it('web: el encargado no puede forzar otra sucursal del grupo', function () {
    $this->actingAs($this->encargado)
        ->from(route('dashboard'))
        ->post(route('branch.store'), ['restaurant_id' => $this->main->id])
        ->assertSessionHasErrors('restaurant_id');
});

it('api: el login informa las sucursales y pide elegir', function () {
    $response = $this->postJson('/api/auth/login', ['username' => 'branch_admin', 'password' => 'password123'])
        ->assertOk();

    expect($response->json('data.user.requires_branch_selection'))->toBeTrue()
        ->and(collect($response->json('data.user.branches'))->pluck('id')->all())
        ->toEqualCanonicalizing([$this->main->id, $this->branch->id]);
});

it('api: select-branch emite un token atado a la sucursal y sobrevive al refresh', function () {
    Table::factory()->create(['restaurant_id' => $this->main->id, 'number' => '901']);
    Table::factory()->create(['restaurant_id' => $this->branch->id, 'number' => '902']);

    $login = $this->postJson('/api/auth/login', ['username' => 'branch_admin', 'password' => 'password123'])->json('data');

    $selected = $this->withHeaders(['Authorization' => 'Bearer '.$login['access_token']])
        ->postJson('/api/auth/select-branch', [
            'restaurant_id' => $this->branch->id,
            'refresh_token' => $login['refresh_token'],
        ])
        ->assertOk()
        ->json('data');

    expect($selected['user']['restaurant_id'])->toBe($this->branch->id)
        ->and($selected['user']['requires_branch_selection'])->toBeFalse();

    $tables = $this->withHeaders(['Authorization' => 'Bearer '.$selected['access_token']])
        ->getJson('/api/tables')
        ->assertOk()
        ->json('data');
    expect(collect($tables)->pluck('number')->all())->toBe(['902']);

    $refreshed = $this->postJson('/api/auth/refresh', ['refresh_token' => $selected['refresh_token']])
        ->assertOk()
        ->json('data');
    expect($refreshed['user']['restaurant_id'])->toBe($this->branch->id)
        ->and($refreshed['user']['requires_branch_selection'])->toBeFalse();
});

it('api: no deja elegir una sucursal ajena', function () {
    $token = $this->postJson('/api/auth/login', ['username' => 'branch_encargado', 'password' => 'password123'])
        ->json('data.access_token');

    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->postJson('/api/auth/select-branch', ['restaurant_id' => $this->main->id])
        ->assertStatus(403);
});

it('api: un usuario de un solo local no tiene que elegir', function () {
    $response = $this->postJson('/api/auth/login', ['username' => 'branch_encargado', 'password' => 'password123'])
        ->assertOk();

    expect($response->json('data.user.requires_branch_selection'))->toBeFalse()
        ->and($response->json('data.user.restaurant_id'))->toBe($this->branch->id);
});

it('crea una sucursal copiando la carta sin copiar el stock', function () {
    $category = \App\Models\Category::factory()->create(['restaurant_id' => $this->main->id]);
    $pizza = \App\Models\Product::factory()->withStock()->create([
        'restaurant_id' => $this->main->id,
        'category_id' => $category->id,
        'name' => 'Pizza',
    ]);
    $pizza->modifiers()->create(['name' => 'Napolitana', 'kind' => 'VARIANT', 'price' => 20000, 'price_modifier' => 0]);
    \App\Models\Stock::create(['restaurant_id' => $this->main->id, 'product_id' => $pizza->id, 'quantity' => 30]);

    $this->actingAs($this->admin);
    $new = app(BranchService::class)->createBranch($this->main, ['name' => 'Sucursal Sur'], copyCatalog: true);

    $copy = \App\Models\Product::withoutGlobalScopes()->where('restaurant_id', $new->id)->where('name', 'Pizza')->first();

    expect($new->parent_id)->toBe($this->main->id)
        ->and($copy)->not->toBeNull()
        ->and($copy->modifiers()->where('kind', 'VARIANT')->count())->toBe(1)
        ->and(\App\Models\Stock::where('restaurant_id', $new->id)->count())->toBe(0);
});

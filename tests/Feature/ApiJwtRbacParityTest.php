<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create(['is_active' => true]);
});

function jwtLogin(string $username, string $password): string
{
    return test()->postJson('/api/auth/login', [
        'username' => $username,
        'password' => $password,
    ])->json('data.access_token');
}

it('GERENTE no tiene comodín ni cocina; sí stock y dashboard', function () {
    User::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'username' => 'gerente_api',
        'password' => Hash::make('password123'),
        'role' => 'GERENTE',
        'is_active' => true,
    ]);

    $token = jwtLogin('gerente_api', 'password123');

    $me = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me')
        ->assertOk()
        ->json('data');

    expect($me['permissions'])->not->toContain('*')
        ->and($me['permissions'])->toContain('stock.read')
        ->and($me['permissions'])->toContain('dashboard.read')
        ->and($me['permissions'])->not->toContain('kitchen.read');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/kitchen/board')
        ->assertStatus(403);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/stock')
        ->assertOk();
});

it('stock permite registrar movimiento ENTRADA', function () {
    User::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'username' => 'admin_stock',
        'password' => Hash::make('password123'),
        'role' => 'ADMIN',
        'is_active' => true,
    ]);

    $category = Category::create([
        'restaurant_id' => $this->restaurant->id,
        'name' => 'General',
        'is_active' => true,
    ]);

    $product = Product::create([
        'restaurant_id' => $this->restaurant->id,
        'category_id' => $category->id,
        'name' => 'Insumo test',
        'price' => 10,
        'type' => 'PRODUCT',
        'is_active' => true,
        'has_stock' => true,
        'stock_minimum' => 1,
    ]);

    Stock::query()->create([
        'restaurant_id' => $this->restaurant->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $token = jwtLogin('admin_stock', 'password123');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/stock/movements', [
            'product_id' => $product->id,
            'type' => 'ENTRADA',
            'quantity' => 3,
            'reason' => 'test',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/stock/movements')
        ->assertOk()
        ->assertJsonPath('success', true);
});

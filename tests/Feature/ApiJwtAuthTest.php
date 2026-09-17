<?php

use App\Models\RefreshToken;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create(['is_active' => true]);
    $this->user = User::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'username' => 'mozo_api',
        'password' => Hash::make('password123'),
        'role' => 'MOZO',
        'is_active' => true,
    ]);
});

it('login JWT devuelve access y refresh', function () {
    $res = $this->postJson('/api/auth/login', [
        'username' => 'mozo_api',
        'password' => 'password123',
    ]);

    $res->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
                'expires_in',
                'refresh_expires_in',
                'user' => ['id', 'username', 'role'],
            ],
        ]);

    expect(RefreshToken::count())->toBe(1);
});

it('refresh rota el token y logout revoca', function () {
    $login = $this->postJson('/api/auth/login', [
        'username' => 'mozo_api',
        'password' => 'password123',
    ])->json('data');

    $refresh = $this->postJson('/api/auth/refresh', [
        'refresh_token' => $login['refresh_token'],
    ]);

    $refresh->assertOk()->assertJsonPath('success', true);
    expect($refresh->json('data.refresh_token'))->not->toBe($login['refresh_token']);

    // token viejo ya no sirve
    $this->postJson('/api/auth/refresh', [
        'refresh_token' => $login['refresh_token'],
    ])->assertStatus(401);

    $newRefresh = $refresh->json('data.refresh_token');
    $this->postJson('/api/auth/logout', [
        'refresh_token' => $newRefresh,
    ])->assertOk();

    $this->postJson('/api/auth/refresh', [
        'refresh_token' => $newRefresh,
    ])->assertStatus(401);
});

it('me requiere bearer JWT', function () {
    $token = $this->postJson('/api/auth/login', [
        'username' => 'mozo_api',
        'password' => 'password123',
    ])->json('data.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.username', 'mozo_api');
});

it('login demo responde mensaje amable sin tokens', function () {
    $res = $this->postJson('/api/auth/login', [
        'username' => 'demo',
        'password' => 'demo1234',
    ]);

    $res->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'DEMO_THANKS')
        ->assertJsonPath('message', 'hola, gracias por probar la app :)');

    expect(RefreshToken::count())->toBe(0);
});

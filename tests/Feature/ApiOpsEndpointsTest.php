<?php

use App\Models\CashRegister;
use App\Models\DeviceToken;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create(['is_active' => true]);
    $this->user = User::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'username' => 'ops_mozo',
        'password' => Hash::make('password123'),
        'role' => 'MOZO',
        'is_active' => true,
    ]);
    $this->token = $this->postJson('/api/auth/login', [
        'username' => 'ops_mozo',
        'password' => 'password123',
    ])->json('data.access_token');
});

function authApi(string $token): array
{
    return ['Authorization' => 'Bearer '.$token];
}

it('lista mesas del restaurante', function () {
    Table::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'number' => '1',
        'status' => 'LIBRE',
    ]);

    $this->withHeaders(authApi($this->token))
        ->getJson('/api/tables')
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('registra device token', function () {
    $this->withHeaders(authApi($this->token))
        ->postJson('/api/devices', [
            'token' => 'ExponentPushToken[test-token-abc]',
            'platform' => 'android',
            'app_version' => '1.0.0',
        ])
        ->assertCreated();

    expect(DeviceToken::where('user_id', $this->user->id)->count())->toBe(1);
});

it('abre caja y resume sesión', function () {
    $cajero = User::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'username' => 'ops_cajero',
        'password' => Hash::make('password123'),
        'role' => 'CAJERO',
        'is_active' => true,
    ]);
    $token = $this->postJson('/api/auth/login', [
        'username' => 'ops_cajero',
        'password' => 'password123',
    ])->json('data.access_token');

    $register = CashRegister::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'is_active' => true,
        'name' => 'Caja 1',
    ]);

    $this->withHeaders(authApi($token))
        ->postJson('/api/cash/registers/'.$register->id.'/open', [
            'initial_amount' => 100,
        ])
        ->assertCreated();

    $this->withHeaders(authApi($token))
        ->getJson('/api/cash/summary')
        ->assertOk()
        ->assertJsonPath('success', true);
});

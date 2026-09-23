<?php

use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethodConfiguration;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\User;
use App\Services\CashRegisterService;
use App\Services\PaymentMethodConfigurationService;
use Illuminate\Support\Facades\Hash;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create(['is_active' => true]);

    $this->admin = User::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'username' => 'pm_admin',
        'password' => Hash::make('password123'),
        'role' => User::ROLE_GERENTE,
        'is_active' => true,
    ]);
    $this->adminToken = $this->postJson('/api/auth/login', [
        'username' => 'pm_admin',
        'password' => 'password123',
    ])->json('data.access_token');

    $this->mozo = User::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'username' => 'pm_mozo',
        'password' => Hash::make('password123'),
        'role' => User::ROLE_MOZO,
        'is_active' => true,
    ]);
    $this->mozoToken = $this->postJson('/api/auth/login', [
        'username' => 'pm_mozo',
        'password' => 'password123',
    ])->json('data.access_token');
});

function authHeaders(string $token): array
{
    return ['Authorization' => 'Bearer '.$token];
}

it('el mozo ve los 4 métodos clásicos activos por defecto sin configuración previa', function () {
    $this->withHeaders(authHeaders($this->mozoToken))
        ->getJson('/api/payment-method-configurations/active')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(4, 'data');
});

it('gerente crea/edita un medio de cobro con alias de transferencia', function () {
    $this->withHeaders(authHeaders($this->adminToken))
        ->postJson('/api/payment-method-configurations', [
            'type' => 'TRANSFERENCIA',
            'alias' => 'negocio.mp',
            'cvu' => '0000003100000000000001',
            'account_holder' => 'Juan Pérez',
        ])
        ->assertCreated()
        ->assertJsonPath('data.alias', 'negocio.mp');

    expect(PaymentMethodConfiguration::where('restaurant_id', $this->restaurant->id)
        ->where('type', 'TRANSFERENCIA')->first()->cvu)->toBe('0000003100000000000001');
});

it('mozo no puede crear ni editar medios de cobro (403)', function () {
    $this->withHeaders(authHeaders($this->mozoToken))
        ->postJson('/api/payment-method-configurations', ['type' => 'TRANSFERENCIA', 'alias' => 'x'])
        ->assertForbidden();
});

it('mozo puede leer los medios activos aunque no pueda editarlos', function () {
    PaymentMethodConfiguration::create([
        'restaurant_id' => $this->restaurant->id,
        'type' => 'TRANSFERENCIA',
        'label' => 'Transferencia',
        'is_active' => true,
        'alias' => 'negocio.mp',
    ]);

    $this->withHeaders(authHeaders($this->mozoToken))
        ->getJson('/api/payment-method-configurations/active')
        ->assertOk()
        ->assertJsonFragment(['alias' => 'negocio.mp']);
});

it('desactivar un medio no borra la fila, solo la marca inactiva', function () {
    $config = PaymentMethodConfiguration::create([
        'restaurant_id' => $this->restaurant->id,
        'type' => 'QR',
        'label' => 'QR',
        'is_active' => true,
    ]);

    $this->withHeaders(authHeaders($this->adminToken))
        ->deleteJson('/api/payment-method-configurations/'.$config->id)
        ->assertOk();

    $config->refresh();
    expect($config->is_active)->toBeFalse();
    expect(PaymentMethodConfiguration::find($config->id))->not->toBeNull();
});

it('una vez configurado, active() solo devuelve los medios activos de ese restaurante', function () {
    PaymentMethodConfiguration::create([
        'restaurant_id' => $this->restaurant->id, 'type' => 'EFECTIVO', 'label' => 'Efectivo', 'is_active' => true,
    ]);
    PaymentMethodConfiguration::create([
        'restaurant_id' => $this->restaurant->id, 'type' => 'QR', 'label' => 'QR', 'is_active' => false,
    ]);

    $data = $this->withHeaders(authHeaders($this->mozoToken))
        ->getJson('/api/payment-method-configurations/active')
        ->assertOk()
        ->json('data');

    expect(collect($data)->pluck('type')->all())->toBe(['EFECTIVO']);
});

it('un restaurante no puede ver ni editar la configuración de otro (aislamiento de tenant)', function () {
    $otherRestaurant = Restaurant::factory()->create(['is_active' => true]);
    $otherConfig = PaymentMethodConfiguration::create([
        'restaurant_id' => $otherRestaurant->id,
        'type' => 'TRANSFERENCIA',
        'label' => 'Transferencia',
        'is_active' => true,
        'alias' => 'secreto-del-otro-restaurante',
    ]);

    $this->withHeaders(authHeaders($this->adminToken))
        ->putJson('/api/payment-method-configurations/'.$otherConfig->id, ['alias' => 'hackeado'])
        ->assertNotFound();

    expect($otherConfig->fresh()->alias)->toBe('secreto-del-otro-restaurante');
});

it('cambiar el alias no modifica pagos ya registrados (el histórico no referencia la config)', function () {
    $service = app(PaymentMethodConfigurationService::class);
    $config = $service->upsert($this->restaurant->id, ['type' => 'TRANSFERENCIA', 'alias' => 'alias.viejo']);

    // Payment no tiene FK a PaymentMethodConfiguration — el registro histórico
    // de "con qué método se cobró" es independiente de "cómo se cobra hoy".
    expect(Payment::query()->getModel()->getFillable())->not->toContain('payment_method_configuration_id');

    $service->upsert($this->restaurant->id, ['type' => 'TRANSFERENCIA', 'alias' => 'alias.nuevo']);
    expect($config->fresh()->alias)->toBe('alias.nuevo');
});

it('el efectivo esperado de caja solo suma EFECTIVO, no tarjeta/transferencia/QR', function () {
    $register = CashRegister::factory()->create(['restaurant_id' => $this->restaurant->id, 'is_active' => true]);
    $session = CashRegisterSession::create([
        'restaurant_id' => $this->restaurant->id,
        'cash_register_id' => $register->id,
        'user_id' => $this->admin->id,
        'initial_amount' => 1000,
        'status' => CashRegisterSession::STATUS_ABIERTA,
        'opened_at' => now(),
    ]);
    $table = Table::factory()->create(['restaurant_id' => $this->restaurant->id]);
    $order = Order::create([
        'restaurant_id' => $this->restaurant->id,
        'table_id' => $table->id,
        'user_id' => $this->admin->id,
        'number' => 'ORD-TEST-'.uniqid(),
        'status' => 'CERRADO',
        'subtotal' => 300,
        'discount' => 0,
        'total' => 300,
    ]);

    foreach ([
        ['method' => 'EFECTIVO', 'amount' => 100],
        ['method' => 'TRANSFERENCIA', 'amount' => 80],
        ['method' => 'QR', 'amount' => 70],
        ['method' => 'DEBITO', 'amount' => 50],
    ] as $p) {
        Payment::create([
            'restaurant_id' => $this->restaurant->id,
            'order_id' => $order->id,
            'cash_register_session_id' => $session->id,
            'user_id' => $this->admin->id,
            'payment_method' => $p['method'],
            'amount' => $p['amount'],
        ]);
    }

    $cash = app(CashRegisterService::class);

    // 1000 inicial + 100 efectivo = 1100 — NO 1000 + 300 (todos los métodos).
    expect($cash->calculateExpectedAmount($session))->toEqual(1100.0);

    $breakdown = $cash->paymentBreakdown($session);
    expect($breakdown)->toMatchArray([
        'EFECTIVO' => 100.0,
        'TRANSFERENCIA' => 80.0,
        'QR' => 70.0,
        'DEBITO' => 50.0,
    ]);
});

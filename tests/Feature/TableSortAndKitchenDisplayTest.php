<?php

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Sector;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create(['is_active' => true]);
    $this->sector = Sector::factory()->create(['restaurant_id' => $this->restaurant->id]);
    $this->admin = User::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'username' => 'sort_admin',
        'password' => Hash::make('password123'),
        'role' => 'ADMIN',
    ]);
    $this->headers = ['Authorization' => 'Bearer '.$this->postJson('/api/auth/login', [
        'username' => 'sort_admin',
        'password' => 'password123',
    ])->json('data.access_token')];

    foreach ([['10', 'LIBRE', 2], ['2', 'OCUPADA', 8], ['1', 'LIBRE', 4], ['1-b', 'LIBRE', 4]] as [$number, $status, $capacity]) {
        Table::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'sector_id' => $this->sector->id,
            'number' => $number,
            'status' => $status,
            'capacity' => $capacity,
        ]);
    }
});

it('la app recibe las mesas en orden natural, igual que la web', function () {
    $numbers = collect($this->withHeaders($this->headers)->getJson('/api/tables')->assertOk()->json('data'))->pluck('number')->all();

    expect($numbers)->toBe(['1', '1-b', '2', '10']);
});

it('la app puede pedir otro orden', function () {
    $byStatus = collect($this->withHeaders($this->headers)->getJson('/api/tables?sort=status')->json('data'))->pluck('number')->all();
    $byCapacity = collect($this->withHeaders($this->headers)->getJson('/api/tables?sort=capacity')->json('data'))->pluck('number')->all();
    $unknown = collect($this->withHeaders($this->headers)->getJson('/api/tables?sort=cualquiera')->json('data'))->pluck('number')->all();

    expect($byStatus)->toBe(['2', '1', '1-b', '10'])
        ->and($byCapacity)->toBe(['2', '1', '1-b', '10'])
        ->and($unknown)->toBe(['1', '1-b', '2', '10']);
});

it('la web ordena con el mismo criterio y recuerda la elección', function () {
    $this->actingAs($this->admin)->withSession(['entry_mode' => 'demo'])
        ->get(route('tables.index', ['sort' => 'status']))
        ->assertOk()
        ->assertSessionHas('tables_sort', 'status')
        ->assertSeeInOrder(['data-table-number="2"', 'data-table-number="1"', 'data-table-number="10"'], false);
});

it('cocina: la pantalla es de solo lectura y se alimenta del tablero JSON', function () {
    $table = Table::where('number', '2')->first();
    Order::factory()->create([
        'restaurant_id' => $this->restaurant->id,
        'table_id' => $table->id,
        'user_id' => $this->admin->id,
        'status' => 'ENVIADO',
    ]);

    $this->actingAs($this->admin)->withSession(['entry_mode' => 'demo'])
        ->get(route('kitchen.index'))
        ->assertOk()
        ->assertDontSee('<select', false)
        ->assertDontSee('kds-status-select', false)
        ->assertSee('board.json', false);

    $this->actingAs($this->admin)->withSession(['entry_mode' => 'demo'])
        ->getJson(route('kitchen.board-json'))
        ->assertOk()
        ->assertJsonPath('counts.ENVIADO', 1)
        ->assertJsonPath('orders.0.place', 'Mesa 2')
        ->assertJsonStructure(['server_time', 'orders' => [['items', 'sent_at', 'status']]]);
});

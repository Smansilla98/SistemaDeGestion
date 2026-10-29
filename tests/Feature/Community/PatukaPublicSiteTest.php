<?php

use App\Models\AgendaNotice;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Workshop;
use App\Models\WorkshopEnrollment;
use App\Support\Branding;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
    $this->workshop = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Percusión', 'status' => 'ABIERTO', 'capacity' => 1, 'fee_cents' => 1500000]);
});

it('muestra talleres abiertos y la agenda pública, no los internos', function () {
    Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Idea a evaluar', 'status' => 'PROPUESTA']);
    Event::query()->create(['restaurant_id' => $this->org->id, 'created_by' => $this->admin->id, 'name' => 'Show de cierre', 'date' => now()->addDays(5)->toDateString(), 'status' => 'PROGRAMADO']);
    AgendaNotice::query()->create(['restaurant_id' => $this->org->id, 'kind' => 'FERIA', 'title' => 'Feria de otoño', 'date' => now()->addDays(8)->toDateString()]);
    AgendaNotice::query()->create(['restaurant_id' => $this->org->id, 'kind' => 'REUNION', 'title' => 'Reunión de equipo', 'date' => now()->addDays(2)->toDateString()]);

    $this->get('/')->assertOk()
        ->assertSee('Percusión')->assertSee('$15.000', false)
        ->assertSee('Show de cierre')->assertSee('Feria de otoño')
        ->assertDontSee('Idea a evaluar')->assertDontSee('Reunión de equipo');
});

it('inscribe desde la web, crea el contacto y avisa al equipo', function () {
    $this->post(route('public.enroll', $this->workshop), ['name' => 'Ana Pérez', 'phone' => '1155550000', 'email' => 'ana@example.com', 'accepts_marketing' => '1'])
        ->assertRedirect(route('public.workshop', $this->workshop))
        ->assertSessionHas('enrolled', fn ($m) => str_contains($m, 'confirmado'));

    $contact = Contact::query()->where('email', 'ana@example.com')->firstOrFail();
    expect($contact->kind)->toBe('ALUMNO')->and($contact->accepts_marketing)->toBeTrue()
        ->and(WorkshopEnrollment::query()->where('contact_id', $contact->id)->value('status'))->toBe(WorkshopEnrollment::ACTIVA)
        ->and(notificationsTitled($this->admin, 'Ana Pérez'))->toBe(1);

    // Cupo lleno: la segunda persona queda en lista de espera.
    $this->post(route('public.enroll', $this->workshop), ['name' => 'Beto Gómez', 'phone' => '1166660000'])
        ->assertSessionHas('enrolled', fn ($m) => str_contains($m, 'lista de espera (#1)'));
});

it('reconoce a quien ya se anotó y no duplica', function () {
    $data = ['name' => 'Ana Pérez', 'phone' => '1155550000'];
    $this->post(route('public.enroll', $this->workshop), $data);
    $this->post(route('public.enroll', $this->workshop), $data)->assertSessionHasErrors('name');

    expect(Contact::query()->where('phone', '1155550000')->count())->toBe(1)
        ->and(WorkshopEnrollment::query()->count())->toBe(1);
});

it('ignora bots y no publica talleres cerrados', function () {
    $this->post(route('public.enroll', $this->workshop), ['name' => 'Bot', 'phone' => '000000', 'website' => 'spam.example'])
        ->assertRedirect();
    expect(WorkshopEnrollment::query()->count())->toBe(0);

    $this->workshop->update(['status' => 'CERRADO']);
    $this->get(route('public.workshop', $this->workshop))->assertNotFound();
    $this->post(route('public.enroll', $this->workshop), ['name' => 'Ana Pérez', 'phone' => '1155550000'])->assertNotFound();
});

it('no muestra talleres de otra organización', function () {
    $other = communityOrg();
    $foreign = Workshop::query()->create(['restaurant_id' => $other->id, 'name' => 'Ajeno', 'status' => 'ABIERTO']);

    $this->get(route('public.workshop', $foreign))->assertNotFound();
});

it('guarda los datos de la web pública desde configuración', function () {
    $this->actingAs($this->admin)->post(route('configuration.update-public'), [
        'about' => 'Cultura en el barrio', 'hours' => 'Mar a dom 17 a 23 h', 'instagram' => '@patuka.ec',
        'whatsapp' => '+54 9 11 2233-4455', 'address' => 'Calle Falsa 123', 'phone' => '', 'email' => 'hola@patuka.test',
    ])->assertSessionHasNoErrors()->assertSessionHas('success');
    auth()->logout();

    $this->get('/')->assertSee('Cultura en el barrio')->assertSee('https://wa.me/5491122334455', false)
        ->assertSee('https://instagram.com/patuka.ec', false)->assertSee('Calle Falsa 123');
});

it('patuka:setup deja la organización lista y crea el admin', function () {
    $this->artisan('patuka:setup', ['--org' => $this->org->id, '--admin-user' => 'patuka', '--admin-password' => 'secreto123'])->assertSuccessful();

    $org = Restaurant::query()->find($this->org->id);
    expect($org->kind)->toBe('ESPACIO_CULTURAL')
        ->and(Branding::palette($org->settings)['primary'])->toBe('#111111')
        ->and(\App\Models\ActivityCategory::withoutGlobalScopes()->where('restaurant_id', $org->id)->count())->toBe(7)
        ->and(\App\Models\User::query()->where('username', 'patuka')->value('role'))->toBe('ADMIN');

    // Idempotente.
    $this->artisan('patuka:setup', ['--org' => $this->org->id])->assertSuccessful();
    expect(\App\Models\ActivityCategory::withoutGlobalScopes()->where('restaurant_id', $org->id)->count())->toBe(7);
});

it('la paleta lee los colores de la marca anterior como negro', function () {
    expect(Branding::palette(['colors' => ['primary' => '#4e8d99', 'secondary' => '#2a5c68']]))
        ->toBe(['primary' => '#111111', 'secondary' => '#000000', 'accent' => '#5c5c5c']);
});

it('patuka:setup --first-run configura una sola vez y respeta los cambios posteriores', function () {
    \App\Models\Restaurant::query()->update(['kind' => 'RESTAURANTE']);

    $this->artisan('patuka:setup', ['--first-run' => true, '--admin-user' => 'patuka', '--admin-password' => 'secreto123'])->assertSuccessful();
    $org = \App\Models\Restaurant::query()->where('name', 'Patuka')->firstOrFail();
    expect($org->kind)->toBe('ESPACIO_CULTURAL')
        ->and(\App\Models\User::query()->where('username', 'patuka')->value('restaurant_id'))->toBe($org->id);

    // El admin cambia un color; el siguiente arranque no lo pisa ni crea otra organización.
    $org->update(['settings' => array_merge($org->settings, ['colors' => ['primary' => '#222222']])]);
    $this->artisan('patuka:setup', ['--first-run' => true])->assertSuccessful();
    expect($org->fresh()->settings['colors']['primary'])->toBe('#222222')
        ->and(\App\Models\Restaurant::query()->where('name', 'Patuka')->count())->toBe(1);
});

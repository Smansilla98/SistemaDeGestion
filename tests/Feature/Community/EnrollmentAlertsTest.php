<?php

use App\Models\Contact;
use App\Models\DeviceToken;
use App\Models\FormSource;
use App\Models\Workshop;
use App\Models\WorkshopEnrollment;
use App\Services\Community\MembershipService;
use App\Services\Forms\FormImportService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    Storage::fake('local');
    Http::fake(['exp.host/*' => Http::response(['data' => []])]);
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
    $this->otherAdmin = orgUser($this->org);
    $this->profe = orgUser(communityOrg(), 'MOZO', ['username' => 'profe-alerta']);
    app(MembershipService::class)->add($this->org->id, 'profe-alerta', 'PROFESOR', $this->admin);
    $this->workshop = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Cerámica', 'status' => 'ABIERTO',
        'teacher_contact_id' => Contact::query()->where('user_id', $this->profe->id)->value('id')]);
});

function lastNotification($user): ?array
{
    return $user->fresh()->notifications()->latest()->first()?->data;
}

it('cada inscripción nueva avisa a coordinación y al profe con quién, qué taller y cuándo, y llega a la app', function () {
    DeviceToken::query()->create(['user_id' => $this->otherAdmin->id, 'restaurant_id' => $this->org->id, 'token' => 'ExponentPushToken[abc]', 'platform' => 'android']);

    $this->actingAs($this->admin)->post(route('workshops.enroll', $this->workshop), ['new_name' => 'Ana Pérez', 'new_phone' => '11 5555 0001'])->assertSessionHasNoErrors();

    $data = lastNotification($this->otherAdmin);
    expect($data['title'])->toBe('Nueva inscripción: Ana Pérez')
        ->and($data['message'])->toContain('Taller: Cerámica')->toContain('Se anotó: '.now()->format('d/m/Y'))
        ->toContain('Tel: 11 5555 0001')->toContain('cargada por '.$this->admin->name)
        ->and($data['url'])->toBe(route('workshops.show', $this->workshop));
    expect(lastNotification($this->profe)['title'])->toBe('Nueva inscripción: Ana Pérez');
    // Quien la cargó no recibe su propio aviso.
    expect(notificationsTitled($this->admin, 'Nueva inscripción'))->toBe(0);

    Http::assertSent(fn ($r) => str_contains($r->url(), 'exp.host') && $r[0]['title'] === 'Nueva inscripción: Ana Pérez' && $r[0]['to'] === 'ExponentPushToken[abc]');

    // En el inicio aparece en «Últimas inscripciones».
    $this->actingAs($this->otherAdmin)->get(route('dashboard'))->assertOk()->assertSee('Últimas inscripciones')->assertSee('Ana Pérez')->assertSee('Cerámica');
});

it('los formularios avisan por persona con la fecha en que se anotó, salvo en la primera carga histórica', function () {
    $this->actingAs($this->admin);
    $h = ['Marca temporal', 'Nombre y apellido', 'Correo', '¿Qué taller?'];
    $source = FormSource::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Inscripciones', 'target' => 'ALUMNOS', 'is_active' => true]);
    $svc = app(FormImportService::class);

    $svc->storeDataset($source, ['headers' => $h, 'rows' => [array_combine($h, ['01/10/2026 09:00:00', 'Vieja Inscripta', 'v@x.com', 'Cerámica'])]]);
    $svc->import($source->fresh());
    expect(notificationsTitled($this->otherAdmin, 'Nueva inscripción'))->toBe(0)
        ->and(notificationsTitled($this->otherAdmin, 'Formulario «Inscripciones»: 1 respuestas nuevas'))->toBe(1);

    $svc->storeDataset($source->fresh(), ['headers' => $h, 'rows' => [
        array_combine($h, ['01/10/2026 09:00:00', 'Vieja Inscripta', 'v@x.com', 'Cerámica']),
        array_combine($h, [now()->subDay()->setTime(18, 45, 10)->format('d/m/Y H:i:s'), 'Nueva Persona', 'n@x.com', 'Cerámica']),
    ]]);
    $svc->import($source->fresh());

    $data = $this->otherAdmin->fresh()->notifications()->get()->first(fn ($n) => ($n->data['title'] ?? '') === 'Nueva inscripción: Nueva Persona')?->data;
    expect($data)->not->toBeNull()
        ->and($data['message'])->toContain('Taller: Cerámica')->toContain('Se anotó: '.now()->subDay()->format('d/m/Y').' 18:45')->toContain('formulario «Inscripciones»');
    expect(notificationsTitled($this->otherAdmin, 'respuestas nuevas'))->toBe(1); // sin un segundo resumen
    $enr = WorkshopEnrollment::query()->whereHas('contact', fn ($q) => $q->where('name', 'Nueva Persona'))->first();
    expect($enr->enrolled_at->format('Y-m-d H:i'))->toBe(now()->subDay()->format('Y-m-d').' 18:45');
});

it('quien desactiva los avisos de inscripciones no los recibe', function () {
    \App\Models\NotificationPreference::query()->create(['user_id' => $this->otherAdmin->id, 'category' => 'inscripciones', 'in_app' => false]);
    $this->actingAs($this->admin)->post(route('workshops.enroll', $this->workshop), ['new_name' => 'Beto']);
    expect(notificationsTitled($this->otherAdmin, 'Nueva inscripción'))->toBe(0)
        ->and(notificationsTitled($this->profe, 'Nueva inscripción'))->toBe(1);
});

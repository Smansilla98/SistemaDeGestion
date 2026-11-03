<?php

use App\Models\Contact;
use App\Models\NotificationPreference;
use App\Models\Workshop;
use App\Models\WorkshopEnrollment;
use App\Models\WorkshopSchedule;
use App\Models\WorkshopSession;
use App\Services\Community\WorkshopService;
use App\Services\Notifier;
use Carbon\CarbonImmutable;

require_once __DIR__.'/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    $this->org = communityOrg();
    $this->admin = orgUser($this->org);
    $this->actingAs($this->admin);
    $this->workshop = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Murga', 'status' => 'ABIERTO', 'capacity' => 2]);
});

function contactFor($org, string $name, $user = null): Contact
{
    return Contact::query()->create(['restaurant_id' => $org->id, 'name' => $name, 'kind' => 'ALUMNO', 'user_id' => $user?->id]);
}

it('respeta el cupo, arma la lista de espera y promueve al cancelar avisando', function () {
    $alumnoUser = orgUser($this->org, 'MOZO');
    $ana = contactFor($this->org, 'Ana');
    $beto = contactFor($this->org, 'Beto');
    $caro = contactFor($this->org, 'Caro', $alumnoUser);
    $svc = app(WorkshopService::class);

    $e1 = $svc->enroll($this->workshop, $ana);
    $svc->enroll($this->workshop, $beto);
    $e3 = $svc->enroll($this->workshop, $caro);

    expect($e1->status)->toBe('ACTIVA')->and($e3->status)->toBe('EN_ESPERA')->and($e3->waitlist_position)->toBe(1);
    expect($alumnoUser->fresh()->notifications()->count())->toBe(1);

    $promoted = $svc->cancel($e1);
    expect($promoted->id)->toBe($e3->id)->and($e3->fresh()->status)->toBe('ACTIVA');
    expect($alumnoUser->fresh()->notifications()->count())->toBe(2);
    expect(notificationsTitled($alumnoUser, 'Tenés lugar'))->toBe(1);

    // No se duplica una inscripción vigente.
    $svc->enroll($this->workshop, $beto);
})->throws(Illuminate\Validation\ValidationException::class);

it('inscribe desde la pantalla creando el contacto si es nuevo', function () {
    $this->post(route('workshops.enroll', $this->workshop), ['new_name' => 'Persona Nueva', 'new_phone' => '11 5555-0000'])->assertSessionHasNoErrors();
    expect(WorkshopEnrollment::query()->where('workshop_id', $this->workshop->id)->count())->toBe(1);
    expect(Contact::query()->where('name', 'Persona Nueva')->value('kind'))->toBe('ALUMNO');
    $this->get(route('workshops.show', $this->workshop))->assertOk()->assertSee('Persona Nueva');
});

it('genera clases de los horarios, reprograma sin perder la fecha original y no duplica', function () {
    WorkshopSchedule::query()->create(['workshop_id' => $this->workshop->id, 'weekday' => 2, 'start_time' => '18:00', 'end_time' => '20:00']);
    $from = CarbonImmutable::parse('next monday');
    $svc = app(WorkshopService::class);
    $u = orgUser($this->org, 'MOZO');
    $svc->enroll($this->workshop, contactFor($this->org, 'Dani', $u));

    expect($svc->generateSessions($this->workshop, $from, $from->addDays(20)))->toBe(3);
    $first = WorkshopSession::query()->where('workshop_id', $this->workshop->id)->orderBy('date')->first();
    $svc->reschedule($first, $from->addDays(3)->toDateString(), '19:00', '21:00', 'Feriado');

    expect($first->fresh()->original_date->toDateString())->toBe($from->addDay()->toDateString());
    expect($svc->generateSessions($this->workshop, $from, $from->addDays(20)))->toBe(0);
    expect(notificationsTitled($u, 'Cambio de horario'))->toBe(1);
});

it('alerta faltas seguidas sin contar justificadas y una presencia corta la racha', function () {
    $svc = app(WorkshopService::class);
    $ana = contactFor($this->org, 'Ana');
    $svc->enroll($this->workshop, $ana);
    $sessions = collect([28, 21, 14, 10, 7])->map(fn ($d) => WorkshopSession::query()->create([
        'workshop_id' => $this->workshop->id, 'date' => today()->subDays($d), 'start_time' => '18:00', 'end_time' => '19:00', 'status' => 'PROGRAMADA',
    ]));
    $mark = fn ($i, $s) => $svc->recordAttendance($sessions[$i], [$ana->id => $s], $this->admin);

    $mark(0, 'PRESENTE');
    $mark(1, 'AUSENTE');
    $mark(2, 'JUSTIFICADA');
    $mark(3, 'AUSENTE');
    expect($svc->consecutiveAbsences($this->workshop))->toHaveCount(0);
    $alerts = $mark(4, 'AUSENTE');

    expect($alerts)->toBe(1);
    expect($svc->consecutiveAbsences($this->workshop)->first()['absences'])->toBe(3);
    expect($this->admin->fresh()->notifications()->first()->data['title'])->toContain('Ana faltó 3 clases seguidas');
    // Guardar de nuevo la misma asistencia no repite el aviso.
    expect($mark(4, 'AUSENTE'))->toBe(0);
});

it('el profesor (por membresía) toma asistencia desde la pantalla', function () {
    $profe = orgUser(communityOrg(), 'MOZO', ['username' => 'profe1']);
    app(\App\Services\Community\MembershipService::class)->add($this->org->id, 'profe1', 'PROFESOR', $this->admin);
    $this->workshop->update(['teacher_contact_id' => Contact::query()->where('user_id', $profe->id)->value('id')]);
    $ana = contactFor($this->org, 'Ana');
    app(WorkshopService::class)->enroll($this->workshop, $ana);
    $session = WorkshopSession::query()->create(['workshop_id' => $this->workshop->id, 'date' => today(), 'start_time' => '18:00', 'end_time' => '19:00', 'status' => 'PROGRAMADA']);

    $this->actingAs($profe)->post(route('branch.store'), ['restaurant_id' => $this->org->id]);
    $this->get(route('workshops.attendance', [$this->workshop, $session]))->assertOk()->assertSee('Ana');
    $this->post(route('workshops.attendance.store', [$this->workshop, $session]), ['status' => [$ana->id => 'TARDE']])->assertRedirect();
    expect($session->attendances()->value('status'))->toBe('TARDE');
    // No puede editar el taller ni inscribir.
    $this->get(route('workshops.edit', $this->workshop))->assertForbidden();
    $this->post(route('workshops.enroll', $this->workshop), ['new_name' => 'X'])->assertForbidden();
});

it('respeta las preferencias y no repite avisos con la misma clave', function () {
    $u = orgUser($this->org, 'MOZO');
    $n = app(Notifier::class);

    expect($n->send($u, 'agenda', 'Hola', 'x', null, 'k1'))->toBeTrue();
    expect($n->send($u, 'agenda', 'Hola', 'x', null, 'k1'))->toBeFalse();
    NotificationPreference::query()->create(['user_id' => $u->id, 'category' => 'agenda', 'in_app' => false, 'email' => false]);
    expect($n->send($u, 'agenda', 'Otra', 'x'))->toBeFalse();
    expect($u->fresh()->notifications()->count())->toBe(1);

    // Abrir una notificación ya leída lleva igual al recurso.
    $id = $u->notifications()->first()->id;
    $this->actingAs($u)->get(route('notifications.read', $id))->assertRedirect();
    $this->get(route('notifications.read', $id))->assertRedirect();
});

it('la agenda del alumno muestra sólo sus clases', function () {
    $alumnoUser = orgUser(communityOrg(), 'MOZO', ['username' => 'alu']);
    app(\App\Services\Community\MembershipService::class)->add($this->org->id, 'alu', 'ALUMNO', $this->admin);
    $contact = Contact::query()->where('user_id', $alumnoUser->id)->first();
    app(WorkshopService::class)->enroll($this->workshop, $contact);
    $otro = Workshop::query()->create(['restaurant_id' => $this->org->id, 'name' => 'Teatro', 'status' => 'ABIERTO']);
    foreach ([$this->workshop, $otro] as $w) {
        WorkshopSession::query()->create(['workshop_id' => $w->id, 'date' => today(), 'start_time' => '18:00', 'end_time' => '19:00', 'status' => 'PROGRAMADA']);
    }

    $this->actingAs($alumnoUser)->post(route('branch.store'), ['restaurant_id' => $this->org->id]);
    $this->get(route('agenda.index'))->assertOk()->assertSee('Murga')->assertDontSee('Teatro');
    $this->get(route('workshops.index'))->assertForbidden();
});

it('el comando de recordatorios avisa clases de mañana una sola vez', function () {
    $u = orgUser($this->org, 'MOZO');
    app(WorkshopService::class)->enroll($this->workshop, contactFor($this->org, 'Eli', $u));
    WorkshopSession::query()->create(['workshop_id' => $this->workshop->id, 'date' => today()->addDay(), 'start_time' => '18:00', 'end_time' => '19:00', 'status' => 'PROGRAMADA']);
    $before = $u->fresh()->notifications()->count();

    $this->artisan('community:reminders')->assertSuccessful();
    $this->artisan('community:reminders')->assertSuccessful();

    expect($u->fresh()->notifications()->count())->toBe($before + 1);
});

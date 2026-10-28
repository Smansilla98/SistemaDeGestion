<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Contact;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopAttendance;
use App\Models\WorkshopEnrollment;
use App\Models\WorkshopSchedule;
use App\Models\WorkshopSession;
use App\Services\Notifier;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Inscripciones con cupo y lista de espera, clases materializadas desde los horarios,
 * reprogramaciones/cancelaciones con aviso, asistencia y alerta de faltas seguidas.
 */
final class WorkshopService
{
    public const MIN_CONSECUTIVE_ABSENCES = 3;

    public function __construct(
        private readonly Notifier $notifier,
        private readonly OrganizationDirectory $directory,
    ) {}

    // ── Inscripciones ─────────────────────────────────────────────────────────

    /**
     * @param  CarbonInterface|null  $signedUpAt  cuándo se anotó (ej. marca temporal del formulario); por defecto ahora
     * @param  string|null  $via  de dónde vino (ej. «Formulario Inscripciones 2027»)
     * @param  bool  $notifyStaff  avisar a coordinación y profe (no en cargas históricas)
     */
    public function enroll(Workshop $workshop, Contact $contact, ?string $note = null, ?CarbonInterface $signedUpAt = null, ?string $via = null, bool $notifyStaff = true): WorkshopEnrollment
    {
        if ((int) $contact->restaurant_id !== (int) $workshop->restaurant_id) {
            throw ValidationException::withMessages(['contact_id' => 'El contacto no pertenece a esta organización.']);
        }
        if (in_array($workshop->status, ['CERRADO', 'FINALIZADO'], true)) {
            throw ValidationException::withMessages(['contact_id' => 'El taller no admite inscripciones.']);
        }

        $enrollment = DB::transaction(function () use ($workshop, $contact, $note, $signedUpAt) {
            // Bloquea el taller: dos inscripciones simultáneas no pueden pasarse del cupo.
            $locked = Workshop::withoutGlobalScopes()->whereKey($workshop->id)->lockForUpdate()->firstOrFail();

            $existing = WorkshopEnrollment::query()->where('workshop_id', $locked->id)->where('contact_id', $contact->id)->first();
            if ($existing && in_array($existing->status, [WorkshopEnrollment::ACTIVA, WorkshopEnrollment::EN_ESPERA], true)) {
                throw ValidationException::withMessages(['contact_id' => "{$contact->name} ya está inscripto/a en este taller."]);
            }

            $hasRoom = $locked->capacity === null
                || WorkshopEnrollment::query()->where('workshop_id', $locked->id)->where('status', WorkshopEnrollment::ACTIVA)->count() < $locked->capacity;

            $data = [
                'status' => $hasRoom ? WorkshopEnrollment::ACTIVA : WorkshopEnrollment::EN_ESPERA,
                'waitlist_position' => $hasRoom ? null : ((int) WorkshopEnrollment::query()->where('workshop_id', $locked->id)
                    ->where('status', WorkshopEnrollment::EN_ESPERA)->max('waitlist_position')) + 1,
                'enrolled_at' => $signedUpAt ?? now(),
                'cancelled_at' => null,
                'note' => $note,
            ];

            if ($existing) {
                $existing->update($data); // re-inscripción: conserva la misma fila e historial

                return $existing;
            }

            return WorkshopEnrollment::query()->create(['workshop_id' => $locked->id, 'contact_id' => $contact->id] + $data);
        });

        $this->notifyContact($contact, 'inscripciones',
            $enrollment->status === WorkshopEnrollment::ACTIVA ? "Inscripción confirmada: {$workshop->name}" : "Lista de espera: {$workshop->name}",
            $enrollment->status === WorkshopEnrollment::ACTIVA
                ? 'Tu lugar en el taller está confirmado.'
                : "El taller está completo. Quedaste en el lugar {$enrollment->waitlist_position} de la lista de espera.",
            "enroll:{$enrollment->id}:{$enrollment->updated_at?->timestamp}");

        if ($notifyStaff) {
            $this->notifyStaffNewEnrollment($workshop, $contact, $enrollment, $via);
        }

        return $enrollment;
    }

    /**
     * Aviso a coordinación y al profe del taller: quién se anotó, a qué taller y cuándo.
     * No le avisa a quien hizo la inscripción a mano (ya lo sabe).
     */
    private function notifyStaffNewEnrollment(Workshop $workshop, Contact $contact, WorkshopEnrollment $enrollment, ?string $via): void
    {
        $recipients = $this->directory->managers((int) $workshop->restaurant_id);
        $teacher = $workshop->teacher()->withoutGlobalScopes()->first()?->user;
        if ($teacher) {
            $recipients->push($teacher);
        }
        $actorId = auth()->id();
        $recipients = $recipients->reject(fn (User $u) => $u->id === $actorId);

        $when = $enrollment->enrolled_at ?? now();
        $parts = [
            "Taller: {$workshop->name}",
            'Se anotó: '.$when->format('d/m/Y H:i'),
            $enrollment->status === WorkshopEnrollment::EN_ESPERA ? "Lista de espera #{$enrollment->waitlist_position}" : 'Inscripción confirmada',
        ];
        if ($contact->phone) {
            $parts[] = "Tel: {$contact->phone}";
        }
        if ($via) {
            $parts[] = "Vía: {$via}";
        }

        $this->notifier->sendMany($recipients, 'inscripciones', "Nueva inscripción: {$contact->name}", implode(' · ', $parts),
            route('workshops.show', $workshop), "new-enrollment:{$enrollment->id}:".$when->timestamp, (int) $workshop->restaurant_id);
    }

    /**
     * Cancela la inscripción. Si liberó un lugar, lo ocupa la primera persona en espera.
     */
    public function cancel(WorkshopEnrollment $enrollment): ?WorkshopEnrollment
    {
        $promoted = DB::transaction(function () use ($enrollment) {
            $workshop = Workshop::withoutGlobalScopes()->whereKey($enrollment->workshop_id)->lockForUpdate()->firstOrFail();
            $wasActive = $enrollment->status === WorkshopEnrollment::ACTIVA;
            $enrollment->update(['status' => WorkshopEnrollment::CANCELADA, 'cancelled_at' => now(), 'waitlist_position' => null]);

            if (! $wasActive) {
                return null;
            }
            $hasRoom = $workshop->capacity === null
                || WorkshopEnrollment::query()->where('workshop_id', $workshop->id)->where('status', WorkshopEnrollment::ACTIVA)->count() < $workshop->capacity;
            if (! $hasRoom) {
                return null;
            }
            $next = WorkshopEnrollment::query()->where('workshop_id', $workshop->id)
                ->where('status', WorkshopEnrollment::EN_ESPERA)->orderBy('waitlist_position')->lockForUpdate()->first();
            $next?->update(['status' => WorkshopEnrollment::ACTIVA, 'waitlist_position' => null]);

            return $next;
        });

        $workshop = $enrollment->workshop()->withoutGlobalScopes()->first();
        $contact = $enrollment->contact()->withoutGlobalScopes()->first();
        if ($contact && $workshop) {
            $this->notifyContact($contact, 'inscripciones', "Inscripción cancelada: {$workshop->name}",
                'Tu inscripción quedó cancelada.', "enroll-cancel:{$enrollment->id}:".now()->timestamp);
        }
        if ($promoted && $workshop) {
            $pc = $promoted->contact()->withoutGlobalScopes()->first();
            if ($pc) {
                $this->notifyContact($pc, 'inscripciones', "¡Tenés lugar en {$workshop->name}!",
                    'Se liberó un cupo y tu inscripción pasó de la lista de espera a confirmada.', "promoted:{$promoted->id}");
            }
        }

        return $promoted;
    }

    // ── Clases ────────────────────────────────────────────────────────────────

    /**
     * Crea las clases de los horarios semanales entre dos fechas. No pisa las existentes:
     * una clase reprogramada o cancelada conserva su estado.
     */
    public function generateSessions(Workshop $workshop, CarbonInterface $from, CarbonInterface $to): int
    {
        $from = CarbonImmutable::parse($from)->startOfDay();
        $to = CarbonImmutable::parse($to)->startOfDay();
        if ($workshop->starts_on && $from->lt($workshop->starts_on)) {
            $from = CarbonImmutable::parse($workshop->starts_on);
        }
        if ($workshop->ends_on && $to->gt($workshop->ends_on)) {
            $to = CarbonImmutable::parse($workshop->ends_on);
        }
        if ($from->gt($to) || $from->diffInDays($to) > 400) {
            return 0;
        }

        $created = 0;
        /** @var WorkshopSchedule $schedule */
        foreach ($workshop->schedules()->get() as $schedule) {
            for ($d = $from; $d->lte($to); $d = $d->addDay()) {
                if ($d->dayOfWeekIso !== $schedule->weekday) {
                    continue;
                }
                $date = $d->toDateString();
                $exists = WorkshopSession::query()->where('workshop_id', $workshop->id)
                    ->where(fn ($q) => $q->where(fn ($q) => $q->whereDate('date', $date)->where('start_time', $schedule->start_time))
                        ->orWhere(fn ($q) => $q->whereDate('original_date', $date)->where('workshop_schedule_id', $schedule->id)))
                    ->exists();
                if ($exists) {
                    continue;
                }
                WorkshopSession::query()->create([
                    'workshop_id' => $workshop->id, 'workshop_schedule_id' => $schedule->id, 'date' => $date,
                    'start_time' => $schedule->start_time, 'end_time' => $schedule->end_time, 'status' => WorkshopSession::PROGRAMADA,
                ]);
                $created++;
            }
        }

        return $created;
    }

    public function reschedule(WorkshopSession $session, string $date, string $start, string $end, ?string $note): WorkshopSession
    {
        $before = $session->date->format('d/m').' '.substr((string) $session->start_time, 0, 5);
        $session->update([
            'original_date' => $session->original_date ?? $session->date,
            'date' => $date, 'start_time' => $start, 'end_time' => $end,
            'status' => WorkshopSession::REPROGRAMADA, 'note' => $note,
        ]);
        $session->refresh();
        $after = $session->date->format('d/m').' '.substr((string) $session->start_time, 0, 5);
        $this->notifyEnrolled($session->workshop, 'clases', "Cambio de horario: {$session->workshop->name}",
            "La clase del {$before} pasa al {$after}.".($note ? " {$note}" : ''), "session-moved:{$session->id}:{$session->updated_at?->timestamp}");

        return $session;
    }

    public function cancelSession(WorkshopSession $session, ?string $note): WorkshopSession
    {
        $session->update(['status' => WorkshopSession::CANCELADA, 'note' => $note]);
        $this->notifyEnrolled($session->workshop, 'clases', "Clase suspendida: {$session->workshop->name}",
            'No hay clase el '.$session->date->format('d/m').'.'.($note ? " {$note}" : ''), "session-cancel:{$session->id}");

        return $session;
    }

    // ── Asistencia ────────────────────────────────────────────────────────────

    /**
     * @param  array<int|string, string>  $statuses  contact_id => PRESENTE|TARDE|AUSENTE|JUSTIFICADA
     * @return int alertas de faltas seguidas enviadas
     */
    public function recordAttendance(WorkshopSession $session, array $statuses, User $by): int
    {
        if ($session->status === WorkshopSession::CANCELADA) {
            throw ValidationException::withMessages(['attendance' => 'La clase está cancelada.']);
        }
        $enrolled = $session->workshop->enrollments()->where('status', WorkshopEnrollment::ACTIVA)->pluck('contact_id')->all();

        DB::transaction(function () use ($session, $statuses, $by, $enrolled) {
            foreach ($statuses as $contactId => $status) {
                if (! in_array((int) $contactId, $enrolled, true) || ! array_key_exists($status, WorkshopAttendance::STATUSES)) {
                    continue;
                }
                WorkshopAttendance::query()->updateOrCreate(
                    ['workshop_session_id' => $session->id, 'contact_id' => (int) $contactId],
                    ['status' => $status, 'recorded_by' => $by->id],
                );
            }
        });

        return $this->alertConsecutiveAbsences($session->workshop);
    }

    /**
     * Alumnos activos con N o más faltas seguidas sin aviso (las justificadas y las clases
     * canceladas no cuentan ni cortan; presente o tarde corta la racha).
     *
     * @return Collection<int, array{contact: Contact, absences: int, since: string}>
     */
    public function consecutiveAbsences(Workshop $workshop, int $min = self::MIN_CONSECUTIVE_ABSENCES): Collection
    {
        $sessionIds = WorkshopSession::query()->where('workshop_id', $workshop->id)
            ->where('status', '!=', WorkshopSession::CANCELADA)->whereDate('date', '<=', today())
            ->orderByDesc('date')->orderByDesc('start_time')->pluck('id');
        if ($sessionIds->isEmpty()) {
            return collect();
        }
        $order = $sessionIds->flip();

        $rows = WorkshopAttendance::query()->whereIn('workshop_session_id', $sessionIds)->get()->groupBy('contact_id');
        $result = collect();
        $enrollments = $workshop->enrollments()->where('status', WorkshopEnrollment::ACTIVA)->with('contact')->get();
        foreach ($enrollments as $enrollment) {
            $list = ($rows[$enrollment->contact_id] ?? collect())->sortBy(fn ($a) => $order[$a->workshop_session_id]);
            $count = 0;
            $since = null;
            foreach ($list as $a) {
                if ($a->status === 'JUSTIFICADA') {
                    continue;
                }
                if (in_array($a->status, ['PRESENTE', 'TARDE'], true)) {
                    break;
                }
                $count++;
                $since = $a->workshop_session_id;
            }
            if ($count >= $min && $enrollment->contact) {
                $result->push(['contact' => $enrollment->contact, 'absences' => $count, 'since' => (string) $since]);
            }
        }

        return $result->sortByDesc('absences')->values();
    }

    private function alertConsecutiveAbsences(Workshop $workshop): int
    {
        $recipients = $this->directory->managers((int) $workshop->restaurant_id);
        $teacherUser = $workshop->teacher()->withoutGlobalScopes()->first()?->user;
        if ($teacherUser) {
            $recipients = $recipients->push($teacherUser);
        }

        $sent = 0;
        foreach ($this->consecutiveAbsences($workshop) as $row) {
            $sent += $this->notifier->sendMany($recipients, 'clases', "{$row['contact']->name} faltó {$row['absences']} clases seguidas",
                "Taller {$workshop->name}: conviene contactarlo/a.", route('workshops.show', $workshop),
                "absences:{$workshop->id}:{$row['contact']->id}:{$row['since']}", (int) $workshop->restaurant_id);
        }

        return $sent;
    }

    // ── Avisos ────────────────────────────────────────────────────────────────

    private function notifyContact(Contact $contact, string $category, string $title, string $message, string $key): void
    {
        $user = $contact->user;
        if ($user instanceof User) {
            $this->notifier->send($user, $category, $title, $message, route('agenda.index'), $key, (int) $contact->restaurant_id);
        }
    }

    private function notifyEnrolled(Workshop $workshop, string $category, string $title, string $message, string $key): int
    {
        $users = $workshop->enrollments()->where('status', WorkshopEnrollment::ACTIVA)->with('contact.user')->get()
            ->map(fn ($e) => $e->contact?->user)->filter();
        $teacher = $workshop->teacher?->user;
        if ($teacher) {
            $users->push($teacher);
        }

        return $this->notifier->sendMany($users, $category, $title, $message, route('agenda.index'), $key, (int) $workshop->restaurant_id);
    }
}

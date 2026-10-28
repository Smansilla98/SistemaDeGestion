<?php

namespace App\Console\Commands;

use App\Models\AgendaNotice;
use App\Models\Consignment;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopEnrollment;
use App\Models\WorkshopSession;
use App\Services\Community\OrganizationDirectory;
use App\Services\Finance\ActivityFinanceService;
use App\Services\Market\ConsignmentService;
use App\Services\Notifier;
use Illuminate\Console\Command;

/**
 * Recordatorios diarios de los módulos de organizaciones. Idempotente: cada aviso tiene
 * una clave de negocio, así que correrlo de más no duplica nada.
 */
class CommunityReminders extends Command
{
    protected $signature = 'community:reminders';

    protected $description = 'Recordatorios de clases, avisos de agenda, aranceles pendientes y consignaciones por liquidar';

    public function handle(Notifier $notifier, OrganizationDirectory $directory, ActivityFinanceService $finance, ConsignmentService $consignments): int
    {
        $sent = 0;
        $tomorrow = today()->addDay()->toDateString();

        // Clases de mañana → alumnos inscriptos con cuenta.
        $sessions = WorkshopSession::query()->with('workshop')->whereDate('date', $tomorrow)->where('status', '!=', WorkshopSession::CANCELADA)->get();
        foreach ($sessions as $s) {
            $workshop = Workshop::withoutGlobalScopes()->find($s->workshop_id);
            if (! $workshop) {
                continue;
            }
            $users = WorkshopEnrollment::query()->where('workshop_id', $workshop->id)->where('status', WorkshopEnrollment::ACTIVA)
                ->with(['contact' => fn ($q) => $q->withoutGlobalScopes()->with('user')])->get()
                ->map(fn ($e) => $e->contact?->user)->filter();
            $sent += $notifier->sendMany($users, 'clases', "Mañana tenés clase: {$workshop->name}",
                'A las '.substr((string) $s->start_time, 0, 5).($workshop->room ? " en {$workshop->room}" : '').'.',
                route('agenda.index'), "session-reminder:{$s->id}", (int) $workshop->restaurant_id);
        }

        // Avisos de agenda de mañana → todos los miembros de la organización.
        foreach (AgendaNotice::withoutGlobalScopes()->whereDate('date', $tomorrow)->get() as $n) {
            $users = $directory->members((int) $n->restaurant_id)->map(fn ($m) => $m['user']);
            $sent += $notifier->sendMany($users, 'agenda', $n->title, $n->body ?: 'Mañana, '.today()->addDay()->format('d/m').'.',
                route('agenda.index'), "notice:{$n->id}", (int) $n->restaurant_id);
        }

        // Aranceles con saldo pendiente → aviso mensual al alumno con cuenta.
        $month = now()->format('Y-m');
        foreach (Workshop::withoutGlobalScopes()->whereIn('status', ['ABIERTO', 'EN_CURSO'])->where('fee_cents', '>', 0)->get() as $w) {
            foreach ($finance->enrollmentBalances($w) as $enrollmentId => $b) {
                if ($b['pending_cents'] <= 0) {
                    continue;
                }
                $user = WorkshopEnrollment::query()->find($enrollmentId)?->contact()->withoutGlobalScopes()->first()?->user;
                if ($user instanceof User) {
                    $sent += $notifier->send($user, 'pagos', "Saldo pendiente: {$w->name}",
                        'Te queda por abonar '.ActivityFinanceService::money($b['pending_cents']).'.', null,
                        "fee-reminder:{$enrollmentId}:{$month}", (int) $w->restaurant_id) ? 1 : 0;
                }
            }
        }

        // Consignaciones con ventas sin liquidar de hace más de 30 días → gestión.
        $old = Consignment::withoutGlobalScopes()->where('status', 'ABIERTA')->whereDate('received_on', '<=', today()->subDays(30))->get();
        foreach ($old as $c) {
            $owed = $consignments->balance($c)['owed_cents'];
            if ($owed <= 0) {
                continue;
            }
            $sent += $notifier->sendMany($directory->managers((int) $c->restaurant_id), 'consignaciones',
                "Consignación #{$c->id} con ventas sin liquidar", 'Se le deben '.ActivityFinanceService::money($owed).' al emprendedor.',
                route('market.consignments.show', $c->id), "consignment-owed:{$c->id}:{$month}", (int) $c->restaurant_id);
        }

        $this->info("Avisos enviados: {$sent}");

        return self::SUCCESS;
    }
}

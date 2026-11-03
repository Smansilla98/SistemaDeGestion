<?php

namespace App\Console\Commands;

use App\Models\Workshop;
use App\Services\Community\WorkshopService;
use Illuminate\Console\Command;

/**
 * Mantiene creadas las clases de las próximas semanas de cada taller abierto o en curso,
 * para que la agenda y el calendario siempre las muestren sin que nadie las genere a mano.
 */
class SyncWorkshopSessions extends Command
{
    protected $signature = 'workshops:sessions {--weeks='.WorkshopService::UPCOMING_WEEKS.' : Semanas hacia adelante}';

    protected $description = 'Crea las clases de las próximas semanas de los talleres abiertos o en curso';

    public function handle(WorkshopService $service): int
    {
        $weeks = max(1, (int) $this->option('weeks'));
        $total = 0;
        Workshop::withoutGlobalScopes()->whereIn('status', WorkshopService::ACTIVE_STATUSES)->with('schedules')
            ->each(function (Workshop $workshop) use ($service, $weeks, &$total) {
                $n = $service->syncUpcomingSessions($workshop, $weeks);
                $total += $n;
                if ($n > 0) {
                    $this->line("{$workshop->name}: {$n} clases nuevas.");
                }
            });
        $this->info("Listo: {$total} clases creadas.");

        return self::SUCCESS;
    }
}

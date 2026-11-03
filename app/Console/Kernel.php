<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Backup diario de la base en el volumen (se conservan los últimos 14).
        $schedule->command('backup:database')->dailyAt('03:00')->withoutOverlapping();
        // Clases de las próximas semanas de cada taller activo (antes de los recordatorios).
        $schedule->command('workshops:sessions')->dailyAt('06:00')->withoutOverlapping();
        // Recordatorios de talleres, agenda, aranceles y consignaciones (idempotente).
        $schedule->command('community:reminders')->dailyAt('09:00')->withoutOverlapping();
        // Respuestas nuevas de los formularios de Google conectados.
        $schedule->command('forms:sync')->everyThirtyMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

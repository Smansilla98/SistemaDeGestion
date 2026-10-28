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

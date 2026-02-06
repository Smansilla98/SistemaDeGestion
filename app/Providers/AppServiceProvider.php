<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Models\Order;
use App\Observers\OrderObserver;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configurar timezone para Carbon
        Carbon::setLocale('es');
        date_default_timezone_set(config('app.timezone'));
        
        // Forzar HTTPS en producción
        if (config('app.env') === 'production' || request()->secure()) {
            URL::forceScheme('https');
        }

        // Registrar Observers
        Order::observe(OrderObserver::class);
    }
}

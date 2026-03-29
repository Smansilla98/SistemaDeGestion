<?php

namespace App\Providers;

use App\Core\Database;
use App\Core\JwtTokenService;
use App\Core\Logger;
use App\Models\Order;
use App\Observers\OrderObserver;
use App\Repositories\ClientRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Logger::class, static fn () => new Logger);
        $this->app->singleton(JwtTokenService::class, static fn () => new JwtTokenService);

        $this->app->bind(ProductRepository::class, static fn () => new ProductRepository(Database::connection()));
        $this->app->bind(UserRepository::class, static fn () => new UserRepository(Database::connection()));
        $this->app->bind(OrderRepository::class, static fn () => new OrderRepository(Database::connection()));
        $this->app->bind(ClientRepository::class, static fn () => new ClientRepository(Database::connection()));
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

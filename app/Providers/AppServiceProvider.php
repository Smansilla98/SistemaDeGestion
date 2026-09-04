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
use App\Support\CurrentRestaurant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $this->app->singleton(CurrentRestaurant::class);

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
        Carbon::setLocale('es');
        date_default_timezone_set(config('app.timezone'));

        if (config('app.env') === 'production' || request()->secure()) {
            URL::forceScheme('https');
        }

        Order::observe(OrderObserver::class);

        Model::shouldBeStrict($this->app->environment('local'));

        DB::whenQueryingForLongerThan(500, function ($connection, $event) {
            $sql = (string) $event->sql;
            if (strlen($sql) > 800) {
                $sql = substr($sql, 0, 800).'…';
            }
            Log::warning('Query lenta', ['sql' => $sql, 'ms' => $event->time]);
        });

        if ($this->app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
                Log::warning('N+1', ['model' => $model::class, 'relation' => $relation]);
            });
        }
    }
}

<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Order;
use App\Models\Table;
use App\Models\Product;
use App\Models\Stock;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Printer;
use App\Models\Sector;
use App\Policies\OrderPolicy;
use App\Policies\TablePolicy;
use App\Policies\ProductPolicy;
use App\Policies\StockPolicy;
use App\Policies\CashRegisterPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\PrinterPolicy;
use App\Policies\SectorPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Order::class => OrderPolicy::class,
        Table::class => TablePolicy::class,
        Product::class => ProductPolicy::class,
        Stock::class => StockPolicy::class,
        CashRegister::class => CashRegisterPolicy::class,
        Category::class => CategoryPolicy::class,
        Printer::class => PrinterPolicy::class,
        Sector::class => SectorPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Gates adicionales para acciones específicas
        Gate::define('manage-restaurant', function ($user) {
            return $user->role === 'ADMIN';
        });

        Gate::define('view-reports', function ($user) {
            return in_array($user->role, ['ADMIN', 'CAJERO']);
        });

        Gate::define('manage-users', function ($user) {
            return $user->role === 'ADMIN';
        });

        Gate::define('manage-configuration', function ($user) {
            return $user->role === 'ADMIN';
        });
    }
}
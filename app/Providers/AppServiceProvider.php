<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use App\Models\Order;
use App\Models\Table;
use App\Models\Product;
use App\Models\Stock;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Printer;
use App\Policies\OrderPolicy;
use App\Policies\TablePolicy;
use App\Policies\ProductPolicy;
use App\Policies\StockPolicy;
use App\Policies\CashRegisterPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\PrinterPolicy;
use App\Observers\OrderObserver;

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
        // Forzar HTTPS en producción
        if (config('app.env') === 'production' || request()->secure()) {
            URL::forceScheme('https');
        }

        // Registrar políticas
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Table::class, TablePolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Stock::class, StockPolicy::class);
        Gate::policy(CashRegister::class, CashRegisterPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Printer::class, PrinterPolicy::class);

        // Registrar Observers
        Order::observe(OrderObserver::class);
    }
}

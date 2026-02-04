<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Table\TableController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Kitchen\KitchenController;
use App\Http\Controllers\CashRegister\CashRegisterController;

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Rutas Protegidas
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    
    // Dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Gestión de Mesas
    |--------------------------------------------------------------------------
    */
    Route::prefix('tables')->name('tables.')->group(function () {
        Route::get('/', [TableController::class, 'index'])->name('index');
        Route::get('/layout/{sectorId?}', [TableController::class, 'layout'])->name('layout');
        Route::get('/{table}/edit', [TableController::class, 'edit'])->name('edit');
        Route::get('/{table}/reserve', [\App\Http\Controllers\TableReservationController::class, 'create'])->name('reserve');
        Route::get('/{table}/orders', [TableController::class, 'tableOrders'])->name('orders');
        Route::post('/{table}/orders', [TableController::class, 'storeOrder'])->name('orders.store');
        Route::post('/subsector-items/{item}/orders', [TableController::class, 'storeOrderFromSubsectorItem'])->name('subsector-items.orders.store');
        Route::get('/{table}/close-summary', [TableController::class, 'closeSummary'])->name('close-summary');
        Route::get('/{table}/consolidated-receipt', [TableController::class, 'consolidatedReceipt'])->name('consolidated-receipt');
        Route::get('/{table}/print-consolidated-receipt', [TableController::class, 'printConsolidatedReceipt'])->name('print-consolidated-receipt');
        Route::get('/{table}/close', [TableController::class, 'showCloseTable'])->name('show-close');
        Route::post('/{table}/close', [TableController::class, 'closeTable'])->name('close');
        Route::post('/{table}/process-payment', [TableController::class, 'processPayment'])->name('process-payment');
        Route::post('/', [TableController::class, 'store'])->name('store');
        Route::post('/layout', [TableController::class, 'updateLayout'])->name('update-layout');
        Route::post('/{table}/reserve', [\App\Http\Controllers\TableReservationController::class, 'store'])->name('reserve.store');
        Route::put('/{table}/status', [TableController::class, 'updateStatus'])->name('update-status');
        Route::put('/{table}', [TableController::class, 'update'])->name('update');
        Route::delete('/{table}', [TableController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Gestión de Pedidos
    |--------------------------------------------------------------------------
    */
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/create/{tableId?}', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/items', [OrderController::class, 'addItem'])->name('add-item');
        Route::put('/{order}/status', [OrderController::class, 'updateStatus'])->name('update-status');
        Route::post('/{order}/send-to-kitchen', [OrderController::class, 'sendToKitchen'])->name('send-to-kitchen');
        Route::post('/{order}/close', [OrderController::class, 'close'])->name('close');
        Route::get('/{order}/summary', [OrderController::class, 'summary'])->name('summary');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
        
        // Pedido rápido (consumo inmediato sin mesa)
        Route::get('/quick-order', [OrderController::class, 'quickOrder'])->name('quick-order');
        Route::post('/quick-order', [OrderController::class, 'processQuickOrder'])->name('process-quick-order');
        
        // Rutas de impresión
        Route::prefix('{order}/print')->name('print.')->group(function () {
            Route::get('/kitchen', [\App\Http\Controllers\Order\OrderPrintController::class, 'kitchenTicket'])->name('kitchen');
            Route::get('/comanda', [\App\Http\Controllers\Order\OrderPrintController::class, 'comanda'])->name('comanda');
            Route::get('/invoice', [\App\Http\Controllers\Order\OrderPrintController::class, 'invoice'])->name('invoice');
            Route::get('/ticket', [\App\Http\Controllers\Order\OrderPrintController::class, 'ticket'])->name('ticket');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Vista de Cocina
    |--------------------------------------------------------------------------
    */
    Route::prefix('kitchen')->name('kitchen.')->middleware('role:COCINA,ADMIN')->group(function () {
        Route::get('/', [KitchenController::class, 'index'])->name('index');
        Route::post('/items/{item}/status', [KitchenController::class, 'updateItemStatus'])->name('update-item-status');
        Route::post('/orders/{order}/ready', [KitchenController::class, 'markOrderReady'])->name('mark-ready');
        Route::put('/orders/{order}/status', [KitchenController::class, 'updateOrderStatus'])->name('update-order-status');
    });
    
    /*
    |--------------------------------------------------------------------------
    | API para Notificaciones (MÓDULO 3)
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/notifications/ready-orders', [KitchenController::class, 'getReadyOrdersNotifications'])->name('ready-orders');
    });

    /*
    |--------------------------------------------------------------------------
    | Módulo de Caja
    |--------------------------------------------------------------------------
    */
    Route::prefix('cash-register')->name('cash-register.')->middleware('role:CAJERO,ADMIN')->group(function () {
        Route::get('/', [CashRegisterController::class, 'index'])->name('index');
        Route::post('/sessions', [CashRegisterController::class, 'openSession'])->name('open-session');
        Route::get('/sessions/{session}', [CashRegisterController::class, 'session'])->name('session');
        Route::post('/sessions/{session}/close', [CashRegisterController::class, 'closeSession'])->name('close-session');
        Route::post('/orders/{order}/payment', [CashRegisterController::class, 'processPayment'])->name('process-payment');
        Route::delete('/movements/{movement}', [CashRegisterController::class, 'destroyMovement'])->name('destroy-movement');
        
        // CRUD de cajas (solo ADMIN)
        Route::middleware('role:ADMIN')->group(function () {
            Route::get('/create', [CashRegisterController::class, 'create'])->name('create');
            Route::post('/', [CashRegisterController::class, 'store'])->name('store');
            Route::get('/{cashRegister}/edit', [CashRegisterController::class, 'edit'])->name('edit');
            Route::put('/{cashRegister}', [CashRegisterController::class, 'update'])->name('update');
            Route::delete('/{cashRegister}', [CashRegisterController::class, 'destroy'])->name('destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Gestión de Productos
    |--------------------------------------------------------------------------
    */
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Category\CategoryController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Category\CategoryController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Category\CategoryController::class, 'store'])->name('store');
        Route::get('/{category}', [\App\Http\Controllers\Category\CategoryController::class, 'show'])->name('show');
        Route::get('/{category}/edit', [\App\Http\Controllers\Category\CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [\App\Http\Controllers\Category\CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [\App\Http\Controllers\Category\CategoryController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Gestión de Sectores (Solo ADMIN)
    |--------------------------------------------------------------------------
    */
    Route::prefix('sectors')->name('sectors.')->middleware('role:ADMIN')->group(function () {
        Route::get('/', [\App\Http\Controllers\Sector\SectorController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Sector\SectorController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Sector\SectorController::class, 'store'])->name('store');
        Route::get('/{sector}', [\App\Http\Controllers\Sector\SectorController::class, 'show'])->name('show');
        Route::get('/{sector}/edit', [\App\Http\Controllers\Sector\SectorController::class, 'edit'])->name('edit');
        Route::put('/{sector}', [\App\Http\Controllers\Sector\SectorController::class, 'update'])->name('update');
        Route::delete('/{sector}', [\App\Http\Controllers\Sector\SectorController::class, 'destroy'])->name('destroy');
        
        // Rutas para items de subsectores
        Route::post('/{sector}/items', [\App\Http\Controllers\Sector\SectorController::class, 'storeItem'])->name('items.store');
        Route::delete('/{sector}/items/{item}', [\App\Http\Controllers\Sector\SectorController::class, 'destroyItem'])->name('items.destroy');
    });

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Product\ProductController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Product\ProductController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Product\ProductController::class, 'store'])->name('store');
        Route::get('/{product}', [\App\Http\Controllers\Product\ProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [\App\Http\Controllers\Product\ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [\App\Http\Controllers\Product\ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [\App\Http\Controllers\Product\ProductController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Control de Stock
    |--------------------------------------------------------------------------
    */
    Route::prefix('stock')->name('stock.')->middleware('role:ADMIN,CAJERO')->group(function () {
        Route::get('/', [\App\Http\Controllers\Stock\StockController::class, 'index'])->name('index');
        Route::get('/movements', [\App\Http\Controllers\Stock\StockController::class, 'movements'])->name('movements');
        Route::get('/movements/create', [\App\Http\Controllers\Stock\StockController::class, 'createMovement'])->name('create-movement');
        Route::post('/movements', [\App\Http\Controllers\Stock\StockController::class, 'storeMovement'])->name('store-movement');
    });

    /*
    |--------------------------------------------------------------------------
    | Reportes
    |--------------------------------------------------------------------------
    */
    Route::prefix('printers')->name('printers.')->middleware('role:ADMIN')->group(function () {
        Route::get('/', [\App\Http\Controllers\PrinterController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\PrinterController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\PrinterController::class, 'store'])->name('store');
        Route::get('/{printer}/edit', [\App\Http\Controllers\PrinterController::class, 'edit'])->name('edit');
        Route::put('/{printer}', [\App\Http\Controllers\PrinterController::class, 'update'])->name('update');
        Route::delete('/{printer}', [\App\Http\Controllers\PrinterController::class, 'destroy'])->name('destroy');
        Route::post('/{printer}/test', [\App\Http\Controllers\PrinterController::class, 'test'])->name('test');
    });

    Route::prefix('reports')->name('reports.')->middleware('role:ADMIN,CAJERO')->group(function () {
        Route::get('/', [\App\Http\Controllers\Report\ReportController::class, 'index'])->name('index');
        Route::get('/sales', [\App\Http\Controllers\Report\ReportController::class, 'sales'])->name('sales');
        Route::get('/sales/export', [\App\Http\Controllers\Report\ReportController::class, 'exportSales'])->name('sales.export');
        Route::get('/products', [\App\Http\Controllers\Report\ReportController::class, 'products'])->name('products');
        Route::get('/staff', [\App\Http\Controllers\Report\ReportController::class, 'staff'])->name('staff');
    });

    /*
    |--------------------------------------------------------------------------
    | Gestión de Usuarios (Solo ADMIN)
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->name('users.')->middleware('role:ADMIN')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\UserController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\User\UserController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\User\UserController::class, 'store'])->name('store');
        Route::get('/{user}', [\App\Http\Controllers\User\UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [\App\Http\Controllers\User\UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [\App\Http\Controllers\User\UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [\App\Http\Controllers\User\UserController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Configuración (Solo ADMIN)
    |--------------------------------------------------------------------------
    */
    Route::prefix('configuration')->name('configuration.')->middleware('role:ADMIN')->group(function () {
        Route::get('/', [\App\Http\Controllers\ConfigurationController::class, 'index'])->name('index');
        Route::post('/visual', [\App\Http\Controllers\ConfigurationController::class, 'updateVisual'])->name('update-visual');
        Route::post('/reset-database', [\App\Http\Controllers\ConfigurationController::class, 'resetDatabase'])->name('reset-database');
    });
});

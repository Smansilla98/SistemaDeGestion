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
        Route::get('/{table}/close-summary', [TableController::class, 'closeSummary'])->name('close-summary');
        Route::get('/{table}/consolidated-receipt', [TableController::class, 'consolidatedReceipt'])->name('consolidated-receipt');
        Route::post('/', [TableController::class, 'store'])->name('store');
        Route::post('/layout', [TableController::class, 'updateLayout'])->name('update-layout');
        Route::post('/{table}/reserve', [\App\Http\Controllers\TableReservationController::class, 'store'])->name('reserve.store');
        Route::post('/{table}/close', [TableController::class, 'closeTable'])->name('close');
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
        Route::post('/{order}/send-to-kitchen', [OrderController::class, 'sendToKitchen'])->name('send-to-kitchen');
        Route::post('/{order}/close', [OrderController::class, 'close'])->name('close');
        Route::get('/{order}/summary', [OrderController::class, 'summary'])->name('summary');
        
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
});

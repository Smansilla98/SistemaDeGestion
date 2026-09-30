<?php

use App\Core\ApiRouter;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ProductApiController as LegacyProductApiController;
use App\Http\Controllers\Api\TableApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API (prefijo /api, middleware group "api" desde RouteServiceProvider)
|--------------------------------------------------------------------------
*/

/** Health / discovery — evita 404 al abrir /api en el navegador o healthchecks. */
Route::get('/', function () {
    return \App\Core\ApiResponse::success([
        'name' => config('app.name', 'Conurbania'),
        'api' => 'jwt',
        'version' => '1.0',
        'docs' => url('/docs'),
        'endpoints' => [
            'POST /api/auth/login',
            'POST /api/auth/refresh',
            'POST /api/auth/logout',
            'GET  /api/auth/me',
            'GET  /api/tables',
            'GET  /api/orders',
            'GET  /api/products',
            'GET  /api/kitchen/board',
            'GET  /api/cash/summary',
            'POST /api/devices',
        ],
    ], 200, 'API Conurbania OK');
})->name('api.health');

Route::get('/health', function () {
    return \App\Core\ApiResponse::success([
        'status' => 'ok',
        'time' => now()->toIso8601String(),
    ]);
})->name('api.health.check');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/** Registro de rutas desde tablas (método + URI + middleware). */
$register = static function (array $routes): void {
    foreach ($routes as $route) {
        Route::middleware($route['middleware'])->match($route['methods'], $route['uri'], $route['action']);
    }
};

$register(ApiRouter::authJwtRoutes());
$register(ApiRouter::sharedRestRoutes());
$register(ApiRouter::adminUserRestRoutes());

/*
|--------------------------------------------------------------------------
| API v1 legada (Sanctum) — clientes existentes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::prefix('tables')->name('api.tables.')->group(function () {
        Route::get('/', [TableApiController::class, 'index'])->name('index');
        Route::get('/{table}', [TableApiController::class, 'show'])->name('show');
        Route::get('/sector/{sectorId}', [TableApiController::class, 'bySector'])->name('by-sector');
    });

    Route::prefix('orders')->name('api.orders.')->group(function () {
        Route::get('/', [OrderApiController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderApiController::class, 'show'])->name('show');
        Route::post('/', [OrderApiController::class, 'store'])->name('store');
        Route::post('/{order}/items', [OrderApiController::class, 'addItem'])->name('add-item');
    });

    Route::prefix('products')->name('api.products.')->group(function () {
        Route::get('/', [LegacyProductApiController::class, 'index'])->name('index');
        Route::get('/{product}', [LegacyProductApiController::class, 'show'])->name('show');
        Route::get('/category/{categoryId}', [LegacyProductApiController::class, 'byCategory'])->name('by-category');
    });
});

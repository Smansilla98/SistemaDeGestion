<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\TableApiController;
use App\Http\Controllers\Api\ProductApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| API REST para Sistema de Restaurante
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Mesas
    Route::prefix('tables')->name('api.tables.')->group(function () {
        Route::get('/', [TableApiController::class, 'index'])->name('index');
        Route::get('/{table}', [TableApiController::class, 'show'])->name('show');
        Route::get('/sector/{sectorId}', [TableApiController::class, 'bySector'])->name('by-sector');
    });

    // Pedidos
    Route::prefix('orders')->name('api.orders.')->group(function () {
        Route::get('/', [OrderApiController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderApiController::class, 'show'])->name('show');
        Route::post('/', [OrderApiController::class, 'store'])->name('store');
        Route::post('/{order}/items', [OrderApiController::class, 'addItem'])->name('add-item');
    });

    // Productos
    Route::prefix('products')->name('api.products.')->group(function () {
        Route::get('/', [ProductApiController::class, 'index'])->name('index');
        Route::get('/{product}', [ProductApiController::class, 'show'])->name('show');
        Route::get('/category/{categoryId}', [ProductApiController::class, 'byCategory'])->name('by-category');
    });
});


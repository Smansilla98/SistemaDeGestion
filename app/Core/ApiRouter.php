<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\Api\AuthJwtController;
use App\Controllers\Api\CashOpsController;
use App\Controllers\Api\ClientController;
use App\Controllers\Api\DeviceController;
use App\Controllers\Api\KitchenOpsController;
use App\Controllers\Api\OrderController;
use App\Controllers\Api\OrderOpsController;
use App\Controllers\Api\ProductController;
use App\Controllers\Api\TableOpsController;
use App\Controllers\Api\UserController;

/**
 * Tabla central método + path + middleware RBAC para la API JWT.
 *
 * @phpstan-type RouteDef array{methods: list<string>, uri: string, action: array{0: class-string, 1: string}, middleware?: list<string>}
 */
final class ApiRouter
{
    /**
     * @return list<RouteDef>
     */
    public static function sharedRestRoutes(): array
    {
        $jwt = ['jwt.auth', 'throttle:jwt-api'];

        return [
            ['methods' => ['GET'], 'uri' => 'products', 'action' => [ProductController::class, 'index'], 'middleware' => array_merge($jwt, ['permission:products.read'])],
            ['methods' => ['GET'], 'uri' => 'products/{id}', 'action' => [ProductController::class, 'show'], 'middleware' => array_merge($jwt, ['permission:products.read'])],
            ['methods' => ['POST'], 'uri' => 'products', 'action' => [ProductController::class, 'store'], 'middleware' => array_merge($jwt, ['permission:products.write'])],
            ['methods' => ['PUT', 'PATCH'], 'uri' => 'products/{id}', 'action' => [ProductController::class, 'update'], 'middleware' => array_merge($jwt, ['permission:products.write'])],
            ['methods' => ['DELETE'], 'uri' => 'products/{id}', 'action' => [ProductController::class, 'destroy'], 'middleware' => array_merge($jwt, ['permission:products.write'])],

            ['methods' => ['GET'], 'uri' => 'orders', 'action' => [OrderController::class, 'index'], 'middleware' => array_merge($jwt, ['permission:orders.read'])],
            ['methods' => ['GET'], 'uri' => 'orders/{id}', 'action' => [OrderController::class, 'show'], 'middleware' => array_merge($jwt, ['permission:orders.read'])],
            ['methods' => ['POST'], 'uri' => 'orders', 'action' => [OrderController::class, 'store'], 'middleware' => array_merge($jwt, ['permission:orders.write'])],
            ['methods' => ['PUT', 'PATCH'], 'uri' => 'orders/{id}', 'action' => [OrderController::class, 'update'], 'middleware' => array_merge($jwt, ['permission:orders.write'])],
            ['methods' => ['DELETE'], 'uri' => 'orders/{id}', 'action' => [OrderController::class, 'destroy'], 'middleware' => array_merge($jwt, ['permission:orders.write'])],
            ['methods' => ['POST'], 'uri' => 'orders/{id}/send-to-kitchen', 'action' => [OrderOpsController::class, 'sendToKitchen'], 'middleware' => array_merge($jwt, ['permission:orders.write'])],
            ['methods' => ['POST'], 'uri' => 'orders/{id}/transition', 'action' => [OrderOpsController::class, 'transition'], 'middleware' => array_merge($jwt, ['permission:orders.write'])],

            ['methods' => ['GET'], 'uri' => 'clients', 'action' => [ClientController::class, 'index'], 'middleware' => array_merge($jwt, ['permission:clients.read'])],
            ['methods' => ['GET'], 'uri' => 'clients/{id}', 'action' => [ClientController::class, 'show'], 'middleware' => array_merge($jwt, ['permission:clients.read'])],
            ['methods' => ['POST'], 'uri' => 'clients', 'action' => [ClientController::class, 'store'], 'middleware' => array_merge($jwt, ['permission:clients.write'])],
            ['methods' => ['PUT', 'PATCH'], 'uri' => 'clients/{id}', 'action' => [ClientController::class, 'update'], 'middleware' => array_merge($jwt, ['permission:clients.write'])],
            ['methods' => ['DELETE'], 'uri' => 'clients/{id}', 'action' => [ClientController::class, 'destroy'], 'middleware' => array_merge($jwt, ['permission:clients.write'])],

            ['methods' => ['GET'], 'uri' => 'tables', 'action' => [TableOpsController::class, 'index'], 'middleware' => array_merge($jwt, ['permission:tables.read'])],
            ['methods' => ['GET'], 'uri' => 'tables/{id}', 'action' => [TableOpsController::class, 'show'], 'middleware' => array_merge($jwt, ['permission:tables.read'])],
            ['methods' => ['POST'], 'uri' => 'tables/{id}/occupy', 'action' => [TableOpsController::class, 'occupy'], 'middleware' => array_merge($jwt, ['permission:tables.write'])],
            ['methods' => ['POST'], 'uri' => 'tables/{id}/pay', 'action' => [TableOpsController::class, 'pay'], 'middleware' => array_merge($jwt, ['permission:cash.write'])],

            ['methods' => ['GET'], 'uri' => 'kitchen/board', 'action' => [KitchenOpsController::class, 'board'], 'middleware' => array_merge($jwt, ['permission:kitchen.read'])],
            ['methods' => ['POST'], 'uri' => 'kitchen/items/{itemId}/status', 'action' => [KitchenOpsController::class, 'updateItemStatus'], 'middleware' => array_merge($jwt, ['permission:kitchen.write'])],

            ['methods' => ['GET'], 'uri' => 'cash/registers', 'action' => [CashOpsController::class, 'registers'], 'middleware' => array_merge($jwt, ['permission:cash.read'])],
            ['methods' => ['GET'], 'uri' => 'cash/session', 'action' => [CashOpsController::class, 'currentSession'], 'middleware' => array_merge($jwt, ['permission:cash.read'])],
            ['methods' => ['GET'], 'uri' => 'cash/summary', 'action' => [CashOpsController::class, 'summary'], 'middleware' => array_merge($jwt, ['permission:cash.read'])],
            ['methods' => ['POST'], 'uri' => 'cash/registers/{registerId}/open', 'action' => [CashOpsController::class, 'open'], 'middleware' => array_merge($jwt, ['permission:cash.write'])],

            ['methods' => ['POST'], 'uri' => 'devices', 'action' => [DeviceController::class, 'store'], 'middleware' => array_merge($jwt, ['permission:devices.write'])],
            ['methods' => ['DELETE'], 'uri' => 'devices', 'action' => [DeviceController::class, 'destroy'], 'middleware' => array_merge($jwt, ['permission:devices.write'])],
            ['methods' => ['POST'], 'uri' => 'devices/test-push', 'action' => [DeviceController::class, 'testPush'], 'middleware' => array_merge($jwt, ['permission:devices.write'])],
        ];
    }

    /**
     * @return list<RouteDef>
     */
    public static function adminUserRestRoutes(): array
    {
        $jwt = ['jwt.auth', 'throttle:jwt-api', 'role:ADMIN,GERENTE'];

        return [
            ['methods' => ['GET'], 'uri' => 'users', 'action' => [UserController::class, 'index'], 'middleware' => array_merge($jwt, ['permission:users.read'])],
            ['methods' => ['GET'], 'uri' => 'users/{id}', 'action' => [UserController::class, 'show'], 'middleware' => array_merge($jwt, ['permission:users.read'])],
            ['methods' => ['POST'], 'uri' => 'users', 'action' => [UserController::class, 'store'], 'middleware' => array_merge($jwt, ['permission:users.write'])],
            ['methods' => ['PUT', 'PATCH'], 'uri' => 'users/{id}', 'action' => [UserController::class, 'update'], 'middleware' => array_merge($jwt, ['permission:users.write'])],
            ['methods' => ['DELETE'], 'uri' => 'users/{id}', 'action' => [UserController::class, 'destroy'], 'middleware' => array_merge($jwt, ['permission:users.write'])],
        ];
    }

    /**
     * @return list<RouteDef>
     */
    public static function authJwtRoutes(): array
    {
        return [
            ['methods' => ['POST'], 'uri' => 'auth/login', 'action' => [AuthJwtController::class, 'login'], 'middleware' => ['throttle:auth-jwt']],
            ['methods' => ['POST'], 'uri' => 'auth/register', 'action' => [AuthJwtController::class, 'register'], 'middleware' => ['throttle:auth-jwt']],
            ['methods' => ['POST'], 'uri' => 'auth/refresh', 'action' => [AuthJwtController::class, 'refresh'], 'middleware' => ['throttle:auth-jwt']],
            ['methods' => ['POST'], 'uri' => 'auth/logout', 'action' => [AuthJwtController::class, 'logout'], 'middleware' => ['throttle:auth-jwt']],
            ['methods' => ['GET'], 'uri' => 'auth/me', 'action' => [AuthJwtController::class, 'me'], 'middleware' => ['jwt.auth', 'throttle:jwt-api', 'permission:auth.me']],
        ];
    }
}

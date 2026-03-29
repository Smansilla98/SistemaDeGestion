<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\Api\AuthJwtController;
use App\Controllers\Api\ClientController;
use App\Controllers\Api\OrderController;
use App\Controllers\Api\ProductController;
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

            ['methods' => ['GET'], 'uri' => 'clients', 'action' => [ClientController::class, 'index'], 'middleware' => array_merge($jwt, ['permission:clients.read'])],
            ['methods' => ['GET'], 'uri' => 'clients/{id}', 'action' => [ClientController::class, 'show'], 'middleware' => array_merge($jwt, ['permission:clients.read'])],
            ['methods' => ['POST'], 'uri' => 'clients', 'action' => [ClientController::class, 'store'], 'middleware' => array_merge($jwt, ['permission:clients.write'])],
            ['methods' => ['PUT', 'PATCH'], 'uri' => 'clients/{id}', 'action' => [ClientController::class, 'update'], 'middleware' => array_merge($jwt, ['permission:clients.write'])],
            ['methods' => ['DELETE'], 'uri' => 'clients/{id}', 'action' => [ClientController::class, 'destroy'], 'middleware' => array_merge($jwt, ['permission:clients.write'])],
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
            ['methods' => ['GET'], 'uri' => 'auth/me', 'action' => [AuthJwtController::class, 'me'], 'middleware' => ['jwt.auth', 'throttle:jwt-api', 'permission:auth.me']],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Pantalla de inicio de cada rol (login y elección de sucursal redirigen acá).
 */
final class HomeRoute
{
    public static function for(User $user): string
    {
        return match ($user->role) {
            User::ROLE_MOZO => 'tables.index',
            User::ROLE_COCINA => 'kitchen.index',
            User::ROLE_CAJERO => 'cash-register.index',
            default => 'dashboard',
        };
    }
}

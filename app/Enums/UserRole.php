<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case CAJERO = 'CAJERO';
    case MOZO = 'MOZO';
    case COCINA = 'COCINA';

    /**
     * Obtener todos los valores
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Obtener permisos del rol
     */
    public function getPermissions(): array
    {
        return match ($this) {
            self::ADMIN => ['*'], // Todos los permisos
            self::MOZO => ['mesas.view', 'mesas.manage', 'pedidos.create', 'pedidos.edit', 'pedidos.view'],
            self::COCINA => ['pedidos.view', 'pedidos.update_status', 'cocina.view'],
            self::CAJERO => ['caja.view', 'caja.manage', 'pagos.create', 'pedidos.view'],
        };
    }
}


<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determinar si el usuario puede ver cualquier pedido
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'ENCARGADO', 'MOZO', 'COCINA', 'CAJERO']);
    }

    /**
     * Determinar si el usuario puede ver el pedido
     */
    public function view(User $user, Order $order): bool
    {
        // Verificar que el pedido pertenece al restaurante del usuario
        if ($user->restaurant_id && $order->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'ENCARGADO', 'MOZO', 'COCINA', 'CAJERO']);
    }

    /**
     * Determinar si el usuario puede crear pedidos
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'ENCARGADO', 'MOZO']);
    }

    /**
     * Determinar si el usuario puede actualizar el pedido
     */
    public function update(User $user, Order $order): bool
    {
        if ($user->restaurant_id && $order->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        // ADMIN y MOZO pueden cambiar el estado de pedidos en flujo activo
        // Flujo simplificado: ABIERTO -> EN_PREPARACION -> ENTREGADO
        // También pueden agregar items a pedidos que no estén cerrados
        if (in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'ENCARGADO', 'MOZO'])) {
            // Permitir actualizar pedidos que no estén cerrados o cancelados
            // Esto incluye agregar items y cerrar pedidos
            return ! in_array($order->status, ['CERRADO', 'CANCELADO']);
        }

        // Cocina ya no tiene acceso (módulo eliminado)
        // Se mantiene por compatibilidad pero no se usa
        if ($user->role === 'COCINA') {
            return in_array($order->status, ['ENVIADO', 'EN_PREPARACION', 'LISTO']);
        }

        return false;
    }

    /**
     * Determinar si el usuario puede eliminar el pedido
     * ADMIN puede eliminar cualquier pedido en cualquier estado (rápidos o de mesa).
     * MOZO y CAJERO solo en ABIERTO, EN_PREPARACION o CANCELADO (sin pagos; lo valida el controller).
     */
    public function delete(User $user, Order $order): bool
    {
        if ($user->restaurant_id && $order->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        if (in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE'])) {
            return true;
        }

        return in_array($user->role, ['MOZO', 'CAJERO'])
            && in_array($order->status, ['ABIERTO', 'EN_PREPARACION', 'CANCELADO']);
    }
}

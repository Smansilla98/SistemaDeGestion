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
        return in_array($user->role, ['ADMIN', 'MOZO', 'COCINA', 'CAJERO']);
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

        return in_array($user->role, ['ADMIN', 'MOZO', 'COCINA', 'CAJERO']);
    }

    /**
     * Determinar si el usuario puede crear pedidos
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'MOZO']);
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
        if (in_array($user->role, ['ADMIN', 'MOZO'])) {
            return in_array($order->status, ['ABIERTO', 'EN_PREPARACION', 'ENTREGADO']);
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
     */
    public function delete(User $user, Order $order): bool
    {
        if ($user->restaurant_id && $order->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        // Solo admin puede eliminar, y solo si está abierto o cancelado
        return $user->role === 'ADMIN' && in_array($order->status, ['ABIERTO', 'CANCELADO']);
    }
}


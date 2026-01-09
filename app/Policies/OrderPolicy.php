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

        // Solo mozos y admin pueden editar pedidos abiertos
        if (in_array($user->role, ['ADMIN', 'MOZO'])) {
            return $order->status === 'ABIERTO';
        }

        // Cocina puede actualizar estado de items
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


<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determinar si el usuario puede ver cualquier producto
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'MOZO', 'CAJERO', 'COCINA']);
    }

    /**
     * Determinar si el usuario puede ver el producto
     */
    public function view(User $user, Product $product): bool
    {
        if ($user->restaurant_id && $product->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, ['ADMIN', 'MOZO', 'CAJERO', 'COCINA']);
    }

    /**
     * Determinar si el usuario puede crear productos
     */
    public function create(User $user): bool
    {
        return $user->role === 'ADMIN';
    }

    /**
     * Determinar si el usuario puede actualizar el producto
     */
    public function update(User $user, Product $product): bool
    {
        if ($user->restaurant_id && $product->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return $user->role === 'ADMIN';
    }

    /**
     * Determinar si el usuario puede eliminar el producto
     */
    public function delete(User $user, Product $product): bool
    {
        if ($user->restaurant_id && $product->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return $user->role === 'ADMIN';
    }
}


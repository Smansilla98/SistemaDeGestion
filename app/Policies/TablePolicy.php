<?php

namespace App\Policies;

use App\Models\Table;
use App\Models\User;

class TablePolicy
{
    /**
     * Determinar si el usuario puede ver cualquier mesa
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'MOZO', 'CAJERO']);
    }

    /**
     * Determinar si el usuario puede ver la mesa
     */
    public function view(User $user, Table $table): bool
    {
        if ($user->restaurant_id && $table->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, ['ADMIN', 'MOZO', 'CAJERO']);
    }

    /**
     * Determinar si el usuario puede crear mesas
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['ADMIN']);
    }

    /**
     * Determinar si el usuario puede actualizar la mesa
     */
    public function update(User $user, Table $table): bool
    {
        if ($user->restaurant_id && $table->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, ['ADMIN', 'MOZO']);
    }

    /**
     * Determinar si el usuario puede eliminar la mesa
     */
    public function delete(User $user, Table $table): bool
    {
        if ($user->restaurant_id && $table->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return $user->role === 'ADMIN';
    }
}


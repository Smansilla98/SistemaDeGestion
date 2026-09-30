<?php

namespace App\Policies;

use App\Models\Table;
use App\Models\User;

class TablePolicy
{
    /** Roles que pueden ver el mapa de mesas. */
    private const VIEW_ROLES = ['SUPERADMIN', 'ADMIN', 'GERENTE', 'ENCARGADO', 'MOZO', 'CAJERO', 'SUPERVISOR'];

    /**
     * Roles que pueden operar una mesa (abrir, cobrar y cerrar).
     * CAJERO y SUPERVISOR estaban excluidos y recibían 403 al intentar cerrar mesas.
     */
    private const UPDATE_ROLES = ['SUPERADMIN', 'ADMIN', 'GERENTE', 'ENCARGADO', 'MOZO', 'CAJERO', 'SUPERVISOR'];

    /**
     * Determinar si el usuario puede ver cualquier mesa
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, self::VIEW_ROLES);
    }

    /**
     * Determinar si el usuario puede ver la mesa
     */
    public function view(User $user, Table $table): bool
    {
        if ($user->restaurant_id && $table->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, self::VIEW_ROLES);
    }

    /**
     * Determinar si el usuario puede crear mesas
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'ENCARGADO']);
    }

    /**
     * Determinar si el usuario puede actualizar la mesa
     */
    public function update(User $user, Table $table): bool
    {
        if ($user->restaurant_id && $table->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, self::UPDATE_ROLES);
    }

    /**
     * Determinar si el usuario puede eliminar la mesa
     */
    public function delete(User $user, Table $table): bool
    {
        if ($user->restaurant_id && $table->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'ENCARGADO']);
    }
}

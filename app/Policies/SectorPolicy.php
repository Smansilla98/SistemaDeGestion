<?php

namespace App\Policies;

use App\Models\Sector;
use App\Models\User;

class SectorPolicy
{
    /**
     * Determinar si el usuario puede ver cualquier sector
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdminLevel();
    }

    /**
     * Determinar si el usuario puede ver el sector
     */
    public function view(User $user, Sector $sector): bool
    {
        if ($user->restaurant_id && $sector->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return $user->isAdminLevel();
    }

    /**
     * Determinar si el usuario puede crear sectores
     */
    public function create(User $user): bool
    {
        return $user->isAdminLevel();
    }

    /**
     * Determinar si el usuario puede actualizar el sector
     */
    public function update(User $user, Sector $sector): bool
    {
        if ($user->restaurant_id && $sector->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return $user->isAdminLevel();
    }

    /**
     * Determinar si el usuario puede eliminar el sector
     */
    public function delete(User $user, Sector $sector): bool
    {
        if ($user->restaurant_id && $sector->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        // Solo admin puede eliminar, y solo si no tiene mesas
        return $user->isAdminLevel() && $sector->tables()->count() === 0;
    }
}

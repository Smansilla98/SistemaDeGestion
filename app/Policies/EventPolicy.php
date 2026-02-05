<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determinar si el usuario puede ver cualquier evento
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'MOZO', 'CAJERO']);
    }

    /**
     * Determinar si el usuario puede ver el evento
     */
    public function view(User $user, Event $event): bool
    {
        if ($user->restaurant_id && $event->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, ['ADMIN', 'MOZO', 'CAJERO']);
    }

    /**
     * Determinar si el usuario puede crear eventos
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'MOZO']);
    }

    /**
     * Determinar si el usuario puede actualizar el evento
     */
    public function update(User $user, Event $event): bool
    {
        if ($user->restaurant_id && $event->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, ['ADMIN', 'MOZO']);
    }

    /**
     * Determinar si el usuario puede eliminar el evento
     */
    public function delete(User $user, Event $event): bool
    {
        if ($user->restaurant_id && $event->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return $user->role === 'ADMIN';
    }
}


<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /** Roles de organizaciones culturales (por membresía) que ven o gestionan eventos. */
    private const COMMUNITY_VIEW = ['COORDINADOR', 'ARTISTA', 'MARKETING'];

    private const COMMUNITY_MANAGE = ['COORDINADOR'];

    /**
     * Determinar si el usuario puede ver cualquier evento
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'MOZO', 'CAJERO', ...self::COMMUNITY_VIEW]);
    }

    /**
     * Determinar si el usuario puede ver el evento
     */
    public function view(User $user, Event $event): bool
    {
        if ($user->restaurant_id && $event->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'MOZO', 'CAJERO', ...self::COMMUNITY_VIEW]);
    }

    /**
     * Determinar si el usuario puede crear eventos
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'MOZO', ...self::COMMUNITY_MANAGE]);
    }

    /**
     * Determinar si el usuario puede actualizar el evento
     */
    public function update(User $user, Event $event): bool
    {
        if ($user->restaurant_id && $event->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'MOZO', ...self::COMMUNITY_MANAGE]);
    }

    /**
     * Determinar si el usuario puede eliminar el evento
     */
    public function delete(User $user, Event $event): bool
    {
        if ($user->restaurant_id && $event->restaurant_id !== $user->restaurant_id) {
            return false;
        }

        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE']);
    }
}

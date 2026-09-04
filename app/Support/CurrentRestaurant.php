<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Tenant actual de la request. Null en console / superadmin sin filtro.
 */
final class CurrentRestaurant
{
    private ?int $forcedId = null;

    public function id(): ?int
    {
        if ($this->forcedId !== null) {
            return $this->forcedId;
        }

        $user = Auth::user();
        if (! $user) {
            return null;
        }

        // SUPERADMIN puede operar cross-tenant; no forzar scope.
        $role = (string) ($user->getAttribute('role') ?? '');
        if ($role === 'SUPERADMIN') {
            return null;
        }

        $restaurantId = $user->getAttribute('restaurant_id');

        return $restaurantId ? (int) $restaurantId : null;
    }

    public function force(?int $id): void
    {
        $this->forcedId = $id;
    }

    public function clear(): void
    {
        $this->forcedId = null;
    }
}

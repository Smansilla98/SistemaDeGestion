<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Qué sucursales puede operar cada usuario.
 *
 * Cada sucursal es un restaurante (tenant). Las sucursales de un mismo negocio
 * comparten casa matriz vía restaurants.parent_id.
 *  - SUPERADMIN: cualquier restaurante activo.
 *  - ADMIN: cualquier sucursal activa de su grupo.
 *  - Resto de los roles: solo su local.
 */
final class BranchAccess
{
    /** Roles que pueden elegir entre las sucursales de su grupo. */
    public const MULTI_BRANCH_ROLES = [User::ROLE_ADMIN];

    /**
     * @return Collection<int, Restaurant>
     */
    public function branchesFor(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return Restaurant::query()->where('is_active', true)->orderBy('name')->get();
        }

        $homeId = $user->homeRestaurantId();
        if ($homeId === null) {
            return new Collection;
        }

        $home = Restaurant::query()->find($homeId);
        if ($home === null) {
            return new Collection;
        }

        if (! in_array($user->role, self::MULTI_BRANCH_ROLES, true)) {
            return new Collection([$home]);
        }

        $rootId = $home->groupRootId();

        return Restaurant::query()
            ->where(fn ($q) => $q->where('id', $rootId)->orWhere('parent_id', $rootId))
            ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $homeId))
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('name')
            ->get();
    }

    public function canAccess(User $user, int $restaurantId): bool
    {
        return $this->branchesFor($user)->contains('id', $restaurantId);
    }

    public function requiresSelection(User $user): bool
    {
        return $this->branchesFor($user)->count() > 1;
    }

    /**
     * @param  Collection<int, Restaurant>  $branches
     * @return list<array{id:int, name:string, address:?string, is_main:bool}>
     */
    public static function toPublicArray(Collection $branches): array
    {
        return $branches->map(fn (Restaurant $r) => [
            'id' => (int) $r->id,
            'name' => (string) $r->name,
            'address' => $r->address,
            'is_main' => $r->parent_id === null,
        ])->values()->all();
    }
}

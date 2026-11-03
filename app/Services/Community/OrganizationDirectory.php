<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Quiénes forman parte de una organización: cuentas propias (users.restaurant_id) más
 * membresías activas, cada una con el rol que tiene en esa organización.
 */
final class OrganizationDirectory
{
    /**
     * @return Collection<int, array{user: User, role: string, via: string}>
     */
    public function members(int $restaurantId): Collection
    {
        $own = User::query()->where('restaurant_id', $restaurantId)->where('is_active', true)->get()
            ->map(fn (User $u) => ['user' => $u, 'role' => (string) $u->homeRole(), 'via' => 'cuenta']);

        $members = OrganizationMembership::query()->with('user')
            ->where('restaurant_id', $restaurantId)->where('is_active', true)->get()
            ->map(fn (OrganizationMembership $m) => $m->user instanceof User && $m->user->is_active
                ? ['user' => $m->user, 'role' => $m->role, 'via' => 'membresía'] : null)
            ->filter();

        return $own->concat($members)->unique(fn ($m) => $m['user']->id)->values();
    }

    /**
     * @param  list<string>  $roles
     * @return Collection<int, User>
     */
    public function usersWithRoles(int $restaurantId, array $roles): Collection
    {
        return $this->members($restaurantId)
            ->filter(fn ($m) => in_array($m['role'], $roles, true))
            ->map(fn ($m) => $m['user'])
            ->values();
    }

    /** Administradores y coordinadores: reciben los avisos de gestión. */
    public function managers(int $restaurantId): Collection
    {
        return $this->usersWithRoles($restaurantId, [User::ROLE_ADMIN, User::ROLE_COORDINADOR, User::ROLE_GERENTE]);
    }

    public function isMember(User $user, int $restaurantId): bool
    {
        return $this->members($restaurantId)->contains(fn ($m) => $m['user']->id === $user->id);
    }
}

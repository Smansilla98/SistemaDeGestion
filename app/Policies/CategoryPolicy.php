<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'CAJERO', 'MOZO']);
    }

    /**
     * Determine if the user can view the category.
     */
    public function view(User $user, Category $category): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'CAJERO', 'MOZO'])
            && $user->restaurant_id === $category->restaurant_id;
    }

    /**
     * Determine if the user can create categories.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'CAJERO']);
    }

    /**
     * Determine if the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'CAJERO'])
            && $user->restaurant_id === $category->restaurant_id;
    }

    /**
     * Determine if the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->isAdminLevel()
            && $user->restaurant_id === $category->restaurant_id;
    }
}

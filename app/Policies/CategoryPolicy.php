<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Category;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO', 'MOZO']);
    }

    /**
     * Determine if the user can view the category.
     */
    public function view(User $user, Category $category): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO', 'MOZO']) 
            && $user->restaurant_id === $category->restaurant_id;
    }

    /**
     * Determine if the user can create categories.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']);
    }

    /**
     * Determine if the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']) 
            && $user->restaurant_id === $category->restaurant_id;
    }

    /**
     * Determine if the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->role === 'ADMIN' 
            && $user->restaurant_id === $category->restaurant_id;
    }
}


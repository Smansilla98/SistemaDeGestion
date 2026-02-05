<?php

namespace App\Policies;

use App\Models\FixedExpense;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FixedExpensePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FixedExpense $fixedExpense): bool
    {
        return $user->restaurant_id === $fixedExpense->restaurant_id && in_array($user->role, ['ADMIN', 'CAJERO']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'ADMIN';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FixedExpense $fixedExpense): bool
    {
        return $user->restaurant_id === $fixedExpense->restaurant_id && $user->role === 'ADMIN';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FixedExpense $fixedExpense): bool
    {
        return $user->restaurant_id === $fixedExpense->restaurant_id && $user->role === 'ADMIN';
    }
}


<?php

namespace App\Policies;

use App\Models\RecurringActivity;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RecurringActivityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'MOZO', 'CAJERO']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RecurringActivity $recurringActivity): bool
    {
        return $user->restaurant_id === $recurringActivity->restaurant_id && in_array($user->role, ['ADMIN', 'MOZO', 'CAJERO']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'MOZO']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RecurringActivity $recurringActivity): bool
    {
        return $user->restaurant_id === $recurringActivity->restaurant_id && in_array($user->role, ['ADMIN', 'MOZO']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RecurringActivity $recurringActivity): bool
    {
        return $user->restaurant_id === $recurringActivity->restaurant_id && $user->role === 'ADMIN';
    }
}


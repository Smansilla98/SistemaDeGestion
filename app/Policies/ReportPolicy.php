<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view reports.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']);
    }

    /**
     * Determine if the user can view sales reports.
     */
    public function viewSales(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']);
    }

    /**
     * Determine if the user can view product reports.
     */
    public function viewProducts(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']);
    }

    /**
     * Determine if the user can view staff reports.
     */
    public function viewStaff(User $user): bool
    {
        return $user->role === 'ADMIN';
    }
}


<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashRegisterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any cash registers.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']);
    }

    /**
     * Determine if the user can view the cash register.
     */
    public function view(User $user, CashRegister $cashRegister): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']) 
            && $user->restaurant_id === $cashRegister->restaurant_id;
    }

    /**
     * Determine if the user can open a cash register session.
     */
    public function openSession(User $user, CashRegister $cashRegister): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']) 
            && $user->restaurant_id === $cashRegister->restaurant_id;
    }

    /**
     * Determine if the user can close a cash register session.
     */
    public function closeSession(User $user, CashRegisterSession $session): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']) 
            && $user->restaurant_id === $session->cashRegister->restaurant_id;
    }

    /**
     * Determine if the user can process payments.
     */
    public function processPayment(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']);
    }
}


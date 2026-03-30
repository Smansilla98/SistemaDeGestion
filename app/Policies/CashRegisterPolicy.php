<?php

namespace App\Policies;

use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashRegisterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any cash registers.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'CAJERO']);
    }

    /**
     * Determine if the user can view the cash register.
     */
    public function view(User $user, CashRegister $cashRegister): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'CAJERO'])
            && $user->restaurant_id === $cashRegister->restaurant_id;
    }

    /**
     * Determine if the user can open a cash register session.
     */
    public function openSession(User $user, CashRegister $cashRegister): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'CAJERO'])
            && $user->restaurant_id === $cashRegister->restaurant_id;
    }

    /**
     * Determine if the user can close a cash register session.
     */
    public function closeSession(User $user, CashRegisterSession $session): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'CAJERO'])
            && $user->restaurant_id === $session->cashRegister->restaurant_id;
    }

    /**
     * Determine if the user can process payments.
     */
    public function processPayment(User $user): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'CAJERO']);
    }

    /**
     * Determine if the user can create cash registers.
     */
    public function create(User $user): bool
    {
        return $user->isAdminLevel();
    }

    /**
     * Determine if the user can update the cash register.
     */
    public function update(User $user, CashRegister $cashRegister): bool
    {
        return $user->isAdminLevel()
            && $user->restaurant_id === $cashRegister->restaurant_id;
    }

    /**
     * Determine if the user can delete the cash register.
     */
    public function delete(User $user, CashRegister $cashRegister): bool
    {
        if (! $user->isAdminLevel() || $user->restaurant_id !== $cashRegister->restaurant_id) {
            return false;
        }

        // No se puede eliminar si tiene sesiones abiertas o históricas
        return $cashRegister->sessions()->count() === 0;
    }
}

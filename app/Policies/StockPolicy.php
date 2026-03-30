<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any stocks.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE', 'CAJERO']);
    }

    /**
     * Determine if the user can view the stock.
     */
    public function view(User $user, Stock $stock): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'CAJERO'])
            && $user->restaurant_id === $stock->restaurant_id;
    }

    /**
     * Determine if the user can create stocks.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE']);
    }

    /**
     * Determine if the user can update the stock.
     */
    public function update(User $user, Stock $stock): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE'])
            && $user->restaurant_id === $stock->restaurant_id;
    }

    /**
     * Determine if the user can delete the stock.
     */
    public function delete(User $user, Stock $stock): bool
    {
        return in_array($user->role, ['SUPERADMIN', 'ADMIN', 'GERENTE'])
            && $user->restaurant_id === $stock->restaurant_id;
    }
}

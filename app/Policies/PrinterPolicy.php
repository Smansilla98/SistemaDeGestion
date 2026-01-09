<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Printer;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrinterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any printers.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']);
    }

    /**
     * Determine if the user can view the printer.
     */
    public function view(User $user, Printer $printer): bool
    {
        return in_array($user->role, ['ADMIN', 'CAJERO']) 
            && $user->restaurant_id === $printer->restaurant_id;
    }

    /**
     * Determine if the user can create printers.
     */
    public function create(User $user): bool
    {
        return $user->role === 'ADMIN';
    }

    /**
     * Determine if the user can update the printer.
     */
    public function update(User $user, Printer $printer): bool
    {
        return $user->role === 'ADMIN' 
            && $user->restaurant_id === $printer->restaurant_id;
    }

    /**
     * Determine if the user can delete the printer.
     */
    public function delete(User $user, Printer $printer): bool
    {
        return $user->role === 'ADMIN' 
            && $user->restaurant_id === $printer->restaurant_id;
    }
}


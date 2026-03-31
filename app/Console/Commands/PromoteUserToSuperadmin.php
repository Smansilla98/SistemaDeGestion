<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserToSuperadmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:promote-superadmin {identifier : ID, username o email del usuario} {--restaurant= : (Opcional) restaurant_id si hay duplicados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promueve un usuario existente al rol SUPERADMIN (solo por CLI).';

    public function handle(): int
    {
        $identifier = (string) $this->argument('identifier');
        $restaurantId = $this->option('restaurant');

        $query = User::query();

        if (ctype_digit($identifier)) {
            $query->where('id', (int) $identifier);
        } else {
            $query->where(function ($q) use ($identifier) {
                $q->where('username', $identifier)->orWhere('email', $identifier);
            });
        }

        if ($restaurantId !== null && $restaurantId !== '') {
            $query->where('restaurant_id', (int) $restaurantId);
        }

        $users = $query->get();

        if ($users->count() === 0) {
            $this->error('No se encontró ningún usuario con ese identificador.');
            return self::FAILURE;
        }

        if ($users->count() > 1) {
            $this->warn('Se encontraron múltiples usuarios. Especifica `--restaurant=<id>` o usa el ID del usuario.');
            $this->table(['id', 'restaurant_id', 'name', 'username', 'email', 'role', 'is_active'], $users->map(function (User $u) {
                return [
                    'id' => $u->id,
                    'restaurant_id' => $u->restaurant_id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'email' => $u->email,
                    'role' => $u->role,
                    'is_active' => $u->is_active ? '1' : '0',
                ];
            })->all());
            return self::FAILURE;
        }

        /** @var User $user */
        $user = $users->first();

        if ($user->role === User::ROLE_SUPERADMIN) {
            $this->info('El usuario ya es SUPERADMIN.');
            return self::SUCCESS;
        }

        if (! $this->confirm("Promover a SUPERADMIN a {$user->name} ({$user->username}) [id={$user->id}]?")) {
            $this->info('Operación cancelada.');
            return self::SUCCESS;
        }

        $user->role = User::ROLE_SUPERADMIN;
        $user->save();

        $this->info('OK. Rol actualizado a SUPERADMIN.');
        return self::SUCCESS;
    }
}


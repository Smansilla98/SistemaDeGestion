<?php

use App\Models\Restaurant;
use App\Models\User;
use App\Services\ModuleLicenseService;
use Illuminate\Support\Facades\Hash;

/**
 * Organización con los módulos de comunidad habilitados (o no) y su admin.
 */
function communityOrg(bool $enabled = true, string $kind = 'ESPACIO_CULTURAL'): Restaurant
{
    $org = Restaurant::factory()->create(['is_active' => true, 'kind' => $kind]);
    if ($enabled) {
        $licenses = app(ModuleLicenseService::class);
        $licenses->save($org, array_fill_keys(array_keys($licenses->catalog()), true));
    }

    return $org->fresh();
}

function orgUser(Restaurant $org, string $role = 'ADMIN', array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'restaurant_id' => $org->id, 'role' => $role, 'is_active' => true, 'password' => Hash::make('password123'),
    ], $attrs));
}

/** Cantidad de notificaciones del usuario cuyo título contiene el texto (el JSON guarda los acentos escapados). */
function notificationsTitled(User $user, string $text): int
{
    return $user->fresh()->notifications()->get()->filter(fn ($n) => str_contains((string) ($n->data['title'] ?? $n->data['message'] ?? ''), $text))->count();
}

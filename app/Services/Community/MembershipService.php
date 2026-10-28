<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Contact;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Suma una cuenta existente a otra organización con un rol, sin crear una cuenta nueva,
 * y la vincula con su ficha de contacto en esa organización.
 */
final class MembershipService
{
    private const CONTACT_KIND_BY_ROLE = [
        User::ROLE_PROFESOR => 'PROFESOR', User::ROLE_ALUMNO => 'ALUMNO', User::ROLE_EMPRENDEDOR => 'EMPRENDEDOR',
        User::ROLE_ARTISTA => 'ARTISTA',
    ];

    public function add(int $restaurantId, string $identifier, string $role, User $by): OrganizationMembership
    {
        if (! in_array($role, User::membershipRoles(), true)) {
            throw ValidationException::withMessages(['role' => 'Rol no válido.']);
        }
        $identifier = trim($identifier);
        $user = User::query()->where('username', $identifier)->orWhere('email', $identifier)->first();
        if (! $user) {
            throw ValidationException::withMessages(['identifier' => 'No existe una cuenta con ese usuario o email.']);
        }
        if ($user->homeRestaurantId() === $restaurantId) {
            throw ValidationException::withMessages(['identifier' => 'Esa cuenta ya es de esta organización; cambiale el rol desde Usuarios.']);
        }
        if ($user->homeRole() === User::ROLE_SUPERADMIN) {
            throw ValidationException::withMessages(['identifier' => 'El superadministrador ya accede a todas las organizaciones.']);
        }

        $membership = OrganizationMembership::query()->updateOrCreate(
            ['user_id' => $user->id, 'restaurant_id' => $restaurantId],
            ['role' => $role, 'is_active' => true, 'created_by' => $by->id],
        );

        $contact = Contact::withoutGlobalScopes()->where('restaurant_id', $restaurantId)->where('user_id', $user->id)->first()
            ?? Contact::withoutGlobalScopes()->where('restaurant_id', $restaurantId)->whereNull('user_id')
                ->whereNotNull('email')->where('email', $user->email)->first();
        if ($contact) {
            $contact->update(['user_id' => $user->id]);
        } else {
            Contact::query()->create([
                'restaurant_id' => $restaurantId, 'user_id' => $user->id, 'name' => $user->name,
                'email' => $user->email, 'kind' => self::CONTACT_KIND_BY_ROLE[$role] ?? 'PARTICIPANTE',
            ]);
        }

        return $membership;
    }
}

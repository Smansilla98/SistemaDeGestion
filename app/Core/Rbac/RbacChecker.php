<?php

declare(strict_types=1);

namespace App\Core\Rbac;

/**
 * Comprueba si un rol posee alguno de los permisos requeridos.
 */
final class RbacChecker
{
    /**
     * @param  list<string>  $required  permisos requeridos (cualquiera basta)
     */
    public static function roleHasAny(?string $role, array $required): bool
    {
        if ($role === null || $role === '') {
            return false;
        }

        $granted = self::permissionsForRole($role);

        if (in_array('*', $granted, true)) {
            return true;
        }

        foreach ($required as $perm) {
            if (in_array($perm, $granted, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lista efectiva de permisos JWT del rol (expande `*` a todos los declarados).
     *
     * @return list<string>
     */
    public static function permissionsForRole(?string $role): array
    {
        if ($role === null || $role === '') {
            return [];
        }

        $map = config('rbac.role_permissions', []);
        /** @var list<string> $granted */
        $granted = $map[$role] ?? [];

        if (in_array('*', $granted, true)) {
            /** @var list<string> $all */
            $all = config('rbac.permissions', []);

            return array_values(array_unique(array_merge(['*'], $all)));
        }

        return array_values($granted);
    }
}

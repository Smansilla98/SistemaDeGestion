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

        $map = config('rbac.role_permissions', []);
        $granted = $map[$role] ?? [];

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
}

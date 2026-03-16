<?php

namespace App\Services;

use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;

class PermissionService
{
    /**
     * Verifica si el usuario tiene permiso (primero override por usuario, luego por rol, luego defaults).
     */
    public function allowed(?User $user, string $permissionKey): bool
    {
        if (!$user) {
            return false;
        }

        // Override a nivel usuario
        $userPerm = UserPermission::where('user_id', $user->id)
            ->where('permission_key', $permissionKey)
            ->first();
        if ($userPerm !== null) {
            return $userPerm->allowed;
        }

        // ADMIN tiene todo permitido por defecto
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        // Permisos del rol en BD
        $rolePerm = RolePermission::where('role', $user->role)
            ->where('permission_key', $permissionKey)
            ->first();
        if ($rolePerm !== null) {
            return $rolePerm->allowed;
        }

        // Default del config para el rol
        $defaults = config('permissions.role_defaults.' . $user->role, []);
        if (is_array($defaults) && array_key_exists($permissionKey, $defaults)) {
            return (bool) $defaults[$permissionKey];
        }

        return false;
    }

    /**
     * Matriz de permisos por rol: [ permission_key => [ 'ADMIN' => true, 'GERENTE' => false, ... ] ]
     */
    public function matrixByRole(): array
    {
        $modules = config('permissions.modules', []);
        $roles = User::getRoles();
        $keys = $this->allPermissionKeys();

        $matrix = [];
        foreach ($keys as $key) {
            $matrix[$key] = [];
            foreach ($roles as $role) {
                $matrix[$key][$role] = $this->allowedForRole($role, $key);
            }
        }

        return $matrix;
    }

    /**
     * Valor de un permiso para un rol (solo rol, sin usuario).
     */
    public function allowedForRole(string $role, string $permissionKey): bool
    {
        if ($role === User::ROLE_ADMIN) {
            return true;
        }
        $row = RolePermission::where('role', $role)->where('permission_key', $permissionKey)->first();
        if ($row !== null) {
            return $row->allowed;
        }
        $defaults = config('permissions.role_defaults.' . $role, []);
        return (bool) ($defaults[$permissionKey] ?? false);
    }

    /**
     * Matriz de permisos efectivos para un usuario (incluye overrides).
     * [ permission_key => true/false ]
     */
    public function matrixForUser(User $user): array
    {
        $keys = $this->allPermissionKeys();
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->allowed($user, $key);
        }
        return $result;
    }

    /**
     * Overrides del usuario (solo lo guardado en user_permissions). Para mostrar en UI "qué tiene custom".
     */
    public function userOverrides(User $user): array
    {
        return UserPermission::where('user_id', $user->id)
            ->pluck('allowed', 'permission_key')
            ->all();
    }

    /**
     * Guardar permiso para un rol.
     */
    public function upsertRolePermission(string $role, string $permissionKey, bool $allowed): void
    {
        RolePermission::updateOrInsert(
            ['role' => $role, 'permission_key' => $permissionKey],
            ['allowed' => $allowed, 'updated_at' => now()]
        );
    }

    /**
     * Guardar permiso para un usuario (override).
     */
    public function upsertUserPermission(int $userId, string $permissionKey, bool $allowed): void
    {
        UserPermission::updateOrInsert(
            ['user_id' => $userId, 'permission_key' => $permissionKey],
            ['allowed' => $allowed, 'updated_at' => now()]
        );
    }

    /**
     * Eliminar override de un usuario para una clave (vuelve a usar el valor del rol).
     */
    public function removeUserOverride(int $userId, string $permissionKey): void
    {
        UserPermission::where('user_id', $userId)->where('permission_key', $permissionKey)->delete();
    }

    /**
     * Lista de todas las claves de permiso (módulo.acción).
     */
    public function allPermissionKeys(): array
    {
        $keys = [];
        foreach (config('permissions.modules', []) as $module) {
            foreach ($module['actions'] as $action) {
                $keys[] = $module['key'] . '.' . $action;
            }
        }
        return $keys;
    }

    /**
     * Módulos con sus acciones (para la tabla de la vista).
     */
    public function getModules(): array
    {
        return config('permissions.modules', []);
    }

    public function getActionLabels(): array
    {
        return config('permissions.action_labels', []);
    }
}

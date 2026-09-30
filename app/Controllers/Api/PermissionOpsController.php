<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Matriz de permisos web (role_permissions / user_permissions) expuesta a mobile.
 */
final class PermissionOpsController extends Controller
{
    public function modules(Request $request, PermissionService $permissions): JsonResponse
    {
        $roles = User::getAssignableRoles($request->user());

        return ApiResponse::success([
            'modules' => $permissions->getModules(),
            'action_labels' => $permissions->getActionLabels(),
            'roles' => $roles,
            'matrix_by_role' => $permissions->matrixByRole($roles),
            'all_keys' => $permissions->allPermissionKeys(),
        ]);
    }

    public function userMatrix(Request $request, int $id, PermissionService $permissions): JsonResponse
    {
        $actor = $request->user();
        $user = User::query()->find($id);
        if ($user === null || (int) $user->restaurant_id !== (int) $actor->restaurant_id) {
            return ApiResponse::error('Usuario no encontrado', 404, 'NOT_FOUND');
        }
        if ($user->isSuperAdmin() && User::shouldHideSuperadminFrom($actor)) {
            return ApiResponse::error('Usuario no encontrado', 404, 'NOT_FOUND');
        }

        $matrix = $permissions->matrixForUser($user);
        $overrides = [];
        foreach ($permissions->allPermissionKeys() as $key) {
            $roleDefault = $permissions->allowedForRole($user->role, $key);
            if (($matrix[$key] ?? false) !== $roleDefault) {
                $overrides[$key] = (bool) ($matrix[$key] ?? false);
            }
        }

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
            ],
            'matrix' => $matrix,
            'overrides' => $overrides,
            'modules' => $permissions->getModules(),
            'action_labels' => $permissions->getActionLabels(),
        ]);
    }

    public function updateUser(Request $request, PermissionService $permissions): JsonResponse
    {
        $actor = $request->user();
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permissions' => 'required|array',
            'permissions.*' => 'boolean',
        ]);

        $user = User::query()->findOrFail($data['user_id']);
        if ((int) $user->restaurant_id !== (int) $actor->restaurant_id) {
            return ApiResponse::error('No podés editar usuarios de otro restaurante', 403, 'FORBIDDEN');
        }
        if ($user->isSuperAdmin() && User::shouldHideSuperadminFrom($actor)) {
            return ApiResponse::error('Usuario no encontrado', 404, 'NOT_FOUND');
        }

        $permissionsInput = $data['permissions'];
        foreach ($permissions->allPermissionKeys() as $key) {
            $wanted = isset($permissionsInput[$key]) && $permissionsInput[$key];
            $roleDefault = $permissions->allowedForRole($user->role, $key);
            if ($wanted === $roleDefault) {
                $permissions->removeUserOverride((int) $user->id, $key);
            } else {
                $permissions->upsertUserPermission((int) $user->id, $key, $wanted);
            }
        }

        return ApiResponse::success(
            ['matrix' => $permissions->matrixForUser($user->fresh())],
            200,
            'Permisos del usuario actualizados'
        );
    }

    public function updateRole(Request $request, PermissionService $permissions): JsonResponse
    {
        $actor = $request->user();
        $data = $request->validate([
            'role' => ['required', 'string', Rule::in(User::getAssignableRoles($actor))],
            'permissions' => 'required|array',
            'permissions.*' => 'boolean',
        ]);

        $permissionsInput = $data['permissions'];
        foreach ($permissions->allPermissionKeys() as $key) {
            $allowed = isset($permissionsInput[$key]) && $permissionsInput[$key];
            $permissions->upsertRolePermission($data['role'], $key, $allowed);
        }

        return ApiResponse::success(
            ['matrix' => $permissions->matrixByRole([$data['role']])[$data['role']] ?? []],
            200,
            'Permisos del rol actualizados'
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * Pantalla principal: pestañas "Por rol" y "Por usuario".
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'roles');
        if (! in_array($tab, ['roles', 'users'], true)) {
            $tab = 'roles';
        }

        $modules = $this->permissionService->getModules();
        $actionLabels = $this->permissionService->getActionLabels();
        $roles = User::getAssignableRoles(auth()->user());
        $matrixByRole = $this->permissionService->matrixByRole($roles);

        // Usuarios del mismo restaurante (para pestaña Por usuario)
        $usersQuery = User::where('restaurant_id', auth()->user()->restaurant_id);
        if (User::shouldHideSuperadminFrom(auth()->user())) {
            $usersQuery->where('role', '!=', User::ROLE_SUPERADMIN);
        }
        $users = $usersQuery->orderBy('name')
            ->get(['id', 'name', 'username', 'role', 'is_active']);

        return view('permissions.index', compact(
            'tab',
            'modules',
            'actionLabels',
            'roles',
            'matrixByRole',
            'users'
        ));
    }

    /**
     * Guardar permisos de un rol (desde la pestaña Por rol).
     */
    public function updateRole(Request $request)
    {
        $request->validate([
            'role' => ['required', 'string', Rule::in(User::getAssignableRoles(auth()->user()))],
            'permissions' => 'required|array',
            'permissions.*' => 'boolean',
        ]);

        $role = $request->input('role');
        $permissions = $request->input('permissions', []);

        foreach ($this->permissionService->allPermissionKeys() as $key) {
            $allowed = isset($permissions[$key]) && $permissions[$key];
            $this->permissionService->upsertRolePermission($role, $key, $allowed);
        }

        return back()->with('success', 'Permisos del rol actualizados correctamente.');
    }

    /**
     * Guardar permisos de un usuario (overrides, desde la pestaña Por usuario).
     * Solo guardamos los que difieren del rol; si no se envía una clave, se elimina el override.
     */
    public function updateUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'permissions' => 'required|array',
            'permissions.*' => 'boolean',
        ]);

        $userId = (int) $request->input('user_id');
        $user = User::findOrFail($userId);

        // Solo mismo restaurante
        if ($user->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No puedes editar permisos de usuarios de otro restaurante.');
        }

        if ($user->isSuperAdmin() && User::shouldHideSuperadminFrom(auth()->user())) {
            abort(404);
        }

        $permissions = $request->input('permissions', []);
        $effectiveMatrix = $this->permissionService->matrixForUser($user);
        $roleMatrix = [];
        foreach ($this->permissionService->allPermissionKeys() as $key) {
            $roleMatrix[$key] = $this->permissionService->allowedForRole($user->role, $key);
        }

        foreach ($this->permissionService->allPermissionKeys() as $key) {
            $wanted = isset($permissions[$key]) && $permissions[$key];
            $roleDefault = $roleMatrix[$key] ?? false;
            if ($wanted === $roleDefault) {
                $this->permissionService->removeUserOverride($userId, $key);
            } else {
                $this->permissionService->upsertUserPermission($userId, $key, $wanted);
            }
        }

        return back()->with('success', 'Permisos del usuario actualizados correctamente.');
    }

    /**
     * API: matriz de permisos efectivos para un usuario (para rellenar el modal/form por usuario).
     */
    public function userMatrix(User $user)
    {
        if ($user->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403);
        }

        if ($user->isSuperAdmin() && User::shouldHideSuperadminFrom(auth()->user())) {
            abort(404);
        }

        return response()->json([
            'matrix' => $this->permissionService->matrixForUser($user),
            'overrides' => $this->permissionService->userOverrides($user),
        ]);
    }
}

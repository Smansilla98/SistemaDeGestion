<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar lista de usuarios
     */
    public function index(Request $request)
    {
        if (! auth()->user()->canManageUsers()) {
            abort(403, 'No tienes permisos para ver usuarios');
        }

        $restaurantId = auth()->user()->restaurant_id;

        $query = User::where('restaurant_id', $restaurantId);
        if (User::shouldHideSuperadminFrom(auth()->user())) {
            $query->where('role', '!=', User::ROLE_SUPERADMIN);
        }

        // Filtro por rol
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount(['tableSessionsAsWaiter' => function ($query) {
            $query->where('status', 'OPEN');
        }])
            ->orderBy('name')
            ->paginate(15);

        $roles = User::getAssignableRoles(auth()->user());

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        if (! auth()->user()->canManageUsers()) {
            abort(403, 'No tienes permisos para crear usuarios');
        }

        $roles = User::getAssignableRoles(auth()->user());

        return view('users.create', compact('roles'));
    }

    /**
     * Guardar nuevo usuario
     */
    public function store(Request $request)
    {
        if (! auth()->user()->canManageUsers()) {
            abort(403, 'No tienes permisos para crear usuarios');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(User::getAssignableRoles(auth()->user()))],
            'is_active' => 'boolean',
        ]);

        if ($validated['role'] === User::ROLE_SUPERADMIN && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Solo un superadmin puede asignar el rol Superadmin.');
        }

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    /**
     * Mostrar usuario específico
     */
    public function show(User $user)
    {
        if (! auth()->user()->canManageUsers()) {
            abort(403, 'No tienes permisos para ver usuarios');
        }

        // Verificar que el usuario pertenezca al mismo restaurante
        if ($user->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a este usuario');
        }

        if ($user->isSuperAdmin() && User::shouldHideSuperadminFrom(auth()->user())) {
            abort(404);
        }

        $user->load(['restaurant', 'orders' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }]);

        // Obtener mesas asignadas actualmente
        $activeTables = $user->getActiveAssignedTables();

        // Obtener sesiones de mesa como mozo
        $tableSessions = $user->tableSessionsAsWaiter()
            ->with(['table.sector'])
            ->orderBy('started_at', 'desc')
            ->limit(20)
            ->get();

        return view('users.show', compact('user', 'activeTables', 'tableSessions'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(User $user)
    {
        if (! auth()->user()->canManageUsers()) {
            abort(403, 'No tienes permisos para editar usuarios');
        }

        // Verificar que el usuario pertenezca al mismo restaurante
        if ($user->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a este usuario');
        }

        if ($user->isSuperAdmin() && User::shouldHideSuperadminFrom(auth()->user())) {
            abort(404);
        }

        $roles = User::getAssignableRoles(auth()->user());

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, User $user)
    {
        if (! auth()->user()->canManageUsers()) {
            abort(403, 'No tienes permisos para editar usuarios');
        }

        // Verificar que el usuario pertenezca al mismo restaurante
        if ($user->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a este usuario');
        }

        if ($user->isSuperAdmin() && User::shouldHideSuperadminFrom(auth()->user())) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(User::getAssignableRoles(auth()->user()))],
            'is_active' => 'boolean',
        ]);

        if ($validated['role'] === User::ROLE_SUPERADMIN && ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Solo un superadmin puede asignar el rol Superadmin.');
        }

        // Solo actualizar password si se proporcionó
        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active', $user->is_active);

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    /**
     * Eliminar usuario
     */
    public function destroy(User $user)
    {
        if (! auth()->user()->canManageUsers()) {
            abort(403, 'No tienes permisos para eliminar usuarios');
        }

        // Verificar que el usuario pertenezca al mismo restaurante
        if ($user->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a este usuario');
        }

        // No permitir eliminar el propio usuario
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario');
        }

        if ($user->isSuperAdmin() && User::shouldHideSuperadminFrom(auth()->user())) {
            abort(404);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }
}

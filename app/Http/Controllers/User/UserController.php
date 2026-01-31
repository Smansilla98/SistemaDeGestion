<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Table;
use App\Models\TableSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        // Solo ADMIN puede ver usuarios
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permisos para ver usuarios');
        }

        $restaurantId = auth()->user()->restaurant_id;
        
        $query = User::where('restaurant_id', $restaurantId);

        // Filtro por rol
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount(['tableSessionsAsWaiter' => function ($query) {
            $query->where('status', 'OPEN');
        }])
        ->orderBy('name')
        ->paginate(15);

        $roles = User::getRoles();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permisos para crear usuarios');
        }

        $roles = User::getRoles();
        return view('users.create', compact('roles'));
    }

    /**
     * Guardar nuevo usuario
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permisos para crear usuarios');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(User::getRoles())],
            'is_active' => 'boolean',
        ]);

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
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permisos para ver usuarios');
        }

        // Verificar que el usuario pertenezca al mismo restaurante
        if ($user->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a este usuario');
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
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permisos para editar usuarios');
        }

        // Verificar que el usuario pertenezca al mismo restaurante
        if ($user->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a este usuario');
        }

        $roles = User::getRoles();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, User $user)
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permisos para editar usuarios');
        }

        // Verificar que el usuario pertenezca al mismo restaurante
        if ($user->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a este usuario');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(User::getRoles())],
            'is_active' => 'boolean',
        ]);

        // Solo actualizar password si se proporcionó
        if (!empty($validated['password'])) {
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
        if (auth()->user()->role !== 'ADMIN') {
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

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }
}


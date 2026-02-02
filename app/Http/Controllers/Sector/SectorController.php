<?php

namespace App\Http\Controllers\Sector;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SectorController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:ADMIN');
    }

    /**
     * Mostrar lista de sectores
     */
    public function index()
    {
        $restaurantId = auth()->user()->restaurant_id;

        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->withCount('tables')
            ->orderBy('name')
            ->paginate(20);

        return view('sectors.index', compact('sectors'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        Gate::authorize('create', Sector::class);

        return view('sectors.create');
    }

    /**
     * Crear nuevo sector
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Sector::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['is_active'] = $request->has('is_active');

        Sector::create($validated);

        return redirect()->route('sectors.index')
            ->with('success', 'Sector creado exitosamente');
    }

    /**
     * Mostrar sector
     */
    public function show(Sector $sector)
    {
        Gate::authorize('view', $sector);

        $sector->load(['tables' => function($query) {
            $query->orderBy('number');
        }]);

        return view('sectors.show', compact('sector'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Sector $sector)
    {
        Gate::authorize('update', $sector);

        return view('sectors.edit', compact('sector'));
    }

    /**
     * Actualizar sector
     */
    public function update(Request $request, Sector $sector)
    {
        Gate::authorize('update', $sector);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $sector->update($validated);

        return redirect()->route('sectors.index')
            ->with('success', 'Sector actualizado exitosamente');
    }

    /**
     * Eliminar sector
     */
    public function destroy(Sector $sector)
    {
        Gate::authorize('delete', $sector);

        // Verificar que no tenga mesas
        if ($sector->tables()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un sector que tiene mesas asignadas');
        }

        $sector->delete();

        return redirect()->route('sectors.index')
            ->with('success', 'Sector eliminado exitosamente');
    }
}


<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Traits\Auditable;

class CategoryController extends Controller
{
    use Auditable;

    /**
     * Mostrar lista de categorías
     * Mejora: Filtrado por sector, búsqueda, ordenamiento, paginación
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Category::class);

        $restaurantId = auth()->user()->restaurant_id;

        $query = Category::where('restaurant_id', $restaurantId)
            ->with(['sector']) // Eager loading
            ->withCount('products');

        // Filtro por sector (OBLIGATORIO según jerarquía)
        if ($request->filled('sector_id')) {
            $query->where('sector_id', $request->sector_id);
        }

        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $allowedSorts = ['name', 'display_order', 'products_count', 'created_at'];
        
        if (in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'products_count') {
                $query->orderBy('products_count', $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->orderBy('display_order')->orderBy('name');
        }

        // Paginación backend
        $categories = $query->paginate(20)->withQueryString();
        
        // Cargar sectores para el filtro
        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->whereNull('parent_id') // Solo sectores principales
            ->orderBy('name')
            ->get();
            
        $selectedSector = $request->filled('sector_id') 
            ? Sector::find($request->sector_id) 
            : null;

        return view('categories.index', compact('categories', 'sectors', 'selectedSector'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(Request $request)
    {
        Gate::authorize('create', Category::class);

        $restaurantId = auth()->user()->restaurant_id;
        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
            
        $selectedSectorId = $request->get('sector_id');

        return view('categories.create', compact('sectors', 'selectedSectorId'));
    }

    /**
     * Crear nueva categoría
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Category::class);

        $validated = $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['is_active'] = $request->has('is_active');

        $category = Category::create($validated);
        
        // Auditoría
        $this->auditCreate($category, $validated);

        return redirect()->route('categories.index', ['sector_id' => $category->sector_id])
            ->with('success', 'Categoría creada exitosamente');
    }

    /**
     * Mostrar categoría
     */
    public function show(Category $category)
    {
        Gate::authorize('view', $category);

        $category->load(['products' => function($query) {
            $query->orderBy('name');
        }]);

        return view('categories.show', compact('category'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Category $category)
    {
        Gate::authorize('update', $category);

        $restaurantId = auth()->user()->restaurant_id;
        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('categories.edit', compact('category', 'sectors'));
    }

    /**
     * Actualizar categoría
     */
    public function update(Request $request, Category $category)
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $oldAttributes = $category->getAttributes();
        $category->update($validated);
        
        // Auditoría
        $this->auditUpdate($category, $oldAttributes, $validated);

        return redirect()->route('categories.index', ['sector_id' => $category->sector_id])
            ->with('success', 'Categoría actualizada exitosamente');
    }

    /**
     * Eliminar categoría
     */
    public function destroy(Category $category)
    {
        Gate::authorize('delete', $category);

        // Verificar que no tenga productos
        if ($category->products()->count() > 0) {
            return back()->with('error', 'No se puede eliminar una categoría que tiene productos asignados');
        }

        // Auditoría antes de eliminar
        $this->auditDelete($category);
        
        $sectorId = $category->sector_id;
        $category->delete();

        return redirect()->route('categories.index', ['sector_id' => $sectorId])
            ->with('success', 'Categoría eliminada exitosamente');
    }
}


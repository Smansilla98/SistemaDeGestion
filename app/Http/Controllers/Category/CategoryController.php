<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
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
            ->withCount('products');

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

        return view('categories.index', compact('categories'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        Gate::authorize('create', Category::class);

        return view('categories.create');
    }

    /**
     * Crear nueva categoría
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Category::class);

        $validated = $request->validate([
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

        return redirect()->route('categories.index')
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

        return view('categories.edit', compact('category'));
    }

    /**
     * Actualizar categoría
     */
    public function update(Request $request, Category $category)
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
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

        return redirect()->route('categories.index')
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
        
        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Categoría eliminada exitosamente');
    }
}


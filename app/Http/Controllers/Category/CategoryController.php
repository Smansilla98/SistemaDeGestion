<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    /**
     * Mostrar lista de categorías
     */
    public function index()
    {
        $restaurantId = auth()->user()->restaurant_id;

        $categories = Category::where('restaurant_id', $restaurantId)
            ->withCount('products')
            ->orderBy('name')
            ->paginate(20);

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
            'is_active' => 'boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['is_active'] = $request->has('is_active');

        Category::create($validated);

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
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $category->update($validated);

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

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Categoría eliminada exitosamente');
    }
}


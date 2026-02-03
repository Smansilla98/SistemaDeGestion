<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Traits\Auditable;

class ProductController extends Controller
{
    use Auditable;

    /**
     * Mostrar lista de productos
     * Mejora: Filtrado por sector y categoría, búsqueda mejorada, ordenamiento
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Product::class);

        $restaurantId = auth()->user()->restaurant_id;

        $query = Product::where('restaurant_id', $restaurantId)
            ->with(['category.sector']); // Eager loading optimizado

        // Filtro por sector (a través de categoría)
        if ($request->filled('sector_id')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('sector_id', $request->sector_id);
            });
        }

        // Filtro por categoría
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Búsqueda mejorada (nombre y descripción)
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
        $allowedSorts = ['name', 'price', 'created_at', 'category_id'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        // Paginación backend real
        $products = $query->paginate(20)->withQueryString();
        
        // Cargar datos para filtros
        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->whereNull('parent_id') // Solo sectores principales
            ->orderBy('name')
            ->get();
            
        $categories = Category::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->when($request->filled('sector_id'), function($q) use ($request) {
                $q->where('sector_id', $request->sector_id);
            })
            ->with('sector')
            ->orderBy('display_order')
            ->get();

        $selectedSector = $request->filled('sector_id') 
            ? Sector::find($request->sector_id) 
            : null;
            
        $selectedCategory = $request->filled('category_id') 
            ? Category::find($request->category_id) 
            : null;

        return view('products.index', compact(
            'products', 
            'categories', 
            'sectors',
            'selectedSector',
            'selectedCategory'
        ));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        Gate::authorize('create', Product::class);

        $restaurantId = auth()->user()->restaurant_id;
        $categories = Category::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return view('products.create', compact('categories'));
    }

    /**
     * Crear nuevo producto
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Product::class);

        // Convertir checkboxes a booleanos ANTES de validar
        $request->merge([
            'has_stock' => $request->boolean('has_stock'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'has_stock' => 'required|boolean',
            'stock_minimum' => 'required_if:has_stock,true|nullable|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;

        $product = Product::create($validated);
        
        // Auditoría
        $this->auditCreate($product, $validated);

        return redirect()->route('products.index')
            ->with('success', 'Producto creado exitosamente');
    }

    /**
     * Mostrar producto
     */
    public function show(Product $product)
    {
        Gate::authorize('view', $product);

        $product->load(['category', 'modifiers', 'stock']);

        return view('products.show', compact('product'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Product $product)
    {
        Gate::authorize('update', $product);

        $restaurantId = auth()->user()->restaurant_id;
        $categories = Category::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Actualizar producto
     */
    public function update(Request $request, Product $product)
    {
        Gate::authorize('update', $product);

        // Convertir checkboxes a booleanos ANTES de validar
        $request->merge([
            'has_stock' => $request->boolean('has_stock'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'has_stock' => 'required|boolean',
            'stock_minimum' => 'required_if:has_stock,true|nullable|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;

        $oldAttributes = $product->getAttributes();
        $product->update($validated);
        
        // Auditoría
        $this->auditUpdate($product, $oldAttributes, $validated);

        return redirect()->route('products.index')
            ->with('success', 'Producto actualizado exitosamente');
    }

    /**
     * Eliminar producto
     */
    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product);

        // Verificar que no tenga pedidos asociados
        if ($product->orderItems()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un producto con pedidos asociados');
        }

        // Auditoría antes de eliminar
        $this->auditDelete($product);
        
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Producto eliminado exitosamente');
    }
}


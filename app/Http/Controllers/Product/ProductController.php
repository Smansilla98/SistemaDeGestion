<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Traits\Auditable;
use App\Services\ProductPricingService;

class ProductController extends Controller
{
    use Auditable;

    public function __construct(
        private ProductPricingService $pricing
    ) {}

    /**
     * Interfaz web de productos. La API REST CRUD vive en App\Controllers\Api\ProductController
     * y delega en App\Services\ProductService + App\Repositories\ProductRepository (PDO).
     */

    /**
     * Mostrar lista de productos
     * Mejora: Filtrado por sector y categoría, búsqueda mejorada, ordenamiento
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Product::class);

        $restaurantId = auth()->user()->restaurant_id;

        $query = Product::where('restaurant_id', $restaurantId)
            ->with(['category.sector', 'supplier']); // Eager loading optimizado

        // Filtro por tipo (PRODUCT o INSUMO)
        if ($request->filled('type') && in_array($request->type, ['PRODUCT', 'INSUMO'])) {
            $query->where('type', $request->type);
        } else {
            // Por defecto mostrar solo productos vendibles si no se especifica
            $query->where('type', 'PRODUCT');
        }

        // Filtro por sector (a través de categoría) - solo para productos
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

        $selectedType = $request->get('type', 'PRODUCT');

        return view('products.index', compact(
            'products', 
            'categories', 
            'sectors',
            'selectedSector',
            'selectedCategory',
            'selectedType'
        ));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(Request $request)
    {
        Gate::authorize('create', Product::class);

        $restaurantId = auth()->user()->restaurant_id;
        $type = $request->get('type', 'PRODUCT'); // PRODUCT o INSUMO
        
        $categories = Category::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
            
        $suppliers = \App\Models\Supplier::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('products.create', compact('categories', 'suppliers', 'type'));
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

        $validated = $request->validate($this->productValidationRules());

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated = $this->applyPricingPermissions($validated);

        $product = Product::create($validated);
        
        // Auditoría
        $this->auditCreate($product, $validated);

        $message = $validated['type'] === 'INSUMO' 
            ? 'Insumo creado exitosamente' 
            : 'Producto creado exitosamente';

        return redirect()->route('products.index', ['type' => $validated['type']])
            ->with('success', $message);
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
        $product->load('ingredients');

        $categories = Category::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $suppliers = \App\Models\Supplier::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $insumos = Product::where('restaurant_id', $restaurantId)
            ->insumos()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('products.edit', compact('product', 'categories', 'suppliers', 'insumos'));
    }

    /**
     * Agregar insumo a la receta del producto
     */
    public function storeIngredient(Request $request, Product $product)
    {
        Gate::authorize('update', $product);

        if ($product->type !== 'PRODUCT') {
            return back()->with('error', 'Solo los productos vendibles pueden tener receta.');
        }

        $validated = $request->validate([
            'ingredient_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit' => 'nullable|string|max:50',
        ]);

        $ingredient = Product::findOrFail($validated['ingredient_id']);
        if ($ingredient->type !== 'INSUMO' || $ingredient->restaurant_id !== $product->restaurant_id) {
            return back()->with('error', 'El insumo no es válido.');
        }
        if ($product->ingredients()->where('ingredient_id', $ingredient->id)->exists()) {
            return back()->with('error', 'Ese insumo ya está en la receta.');
        }

        $product->ingredients()->attach($ingredient->id, [
            'quantity' => (int) round($validated['quantity']),
            'unit' => $validated['unit'] ?? $ingredient->unit,
        ]);

        return back()->with('success', 'Insumo agregado a la receta.');
    }

    /**
     * Quitar insumo de la receta del producto
     */
    public function destroyIngredient(Product $product, Product $ingredient)
    {
        Gate::authorize('update', $product);

        if (!$product->ingredients()->where('ingredient_id', $ingredient->id)->exists()) {
            return back()->with('error', 'El insumo no está en la receta.');
        }

        $product->ingredients()->detach($ingredient->id);

        return back()->with('success', 'Insumo quitado de la receta.');
    }

    /**
     * Matriz de edición masiva de precios (solo productos vendibles).
     */
    public function bulkPricing(Request $request)
    {
        Gate::authorize('managePricing', Product::class);

        $restaurantId = auth()->user()->restaurant_id;

        $query = Product::where('restaurant_id', $restaurantId)
            ->where('type', 'PRODUCT')
            ->with('category')
            ->orderBy('name');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('only_without_cost')) {
            $query->whereNull('cost_price');
        }

        $products = $query->get();

        $categories = Category::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return view('products.bulk-pricing', compact('products', 'categories'));
    }

    /**
     * Guardar precios de varios productos a la vez.
     */
    public function bulkPricingUpdate(Request $request)
    {
        Gate::authorize('managePricing', Product::class);

        $restaurantId = auth()->user()->restaurant_id;

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.cost_price' => 'nullable|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.profit_margin' => 'nullable|numeric|min:0|max:100000',
            'items.*.pricing_source' => 'nullable|in:sale,margin',
        ]);

        $updated = 0;

        foreach ($validated['items'] as $item) {
            $product = Product::where('restaurant_id', $restaurantId)
                ->where('type', 'PRODUCT')
                ->where('id', $item['id'])
                ->first();

            if ($product === null) {
                continue;
            }

            Gate::authorize('update', $product);

            $payload = [
                'cost_price' => $item['cost_price'] ?? null,
                'price' => $item['price'],
                'profit_margin' => $item['profit_margin'] ?? null,
            ];

            if (($item['pricing_source'] ?? 'sale') === 'margin') {
                $payload = $this->pricing->apply($payload, $product->getAttributes());
            }

            unset($payload['profit_margin']);

            $oldAttributes = $product->getAttributes();
            $product->update($payload);
            $this->auditUpdate($product, $oldAttributes, $payload);
            $updated++;
        }

        return redirect()
            ->route('products.bulk-pricing', $request->only(['category_id', 'search', 'only_without_cost']))
            ->with('success', "Se actualizaron {$updated} productos.");
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

        $validated = $request->validate($this->productValidationRules());

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated = $this->applyPricingPermissions($validated, $product);

        $oldAttributes = $product->getAttributes();
        $product->update($validated);
        
        // Auditoría
        $this->auditUpdate($product, $oldAttributes, $validated);

        $message = $validated['type'] === 'INSUMO' 
            ? 'Insumo actualizado exitosamente' 
            : 'Producto actualizado exitosamente';

        return redirect()->route('products.index', ['type' => $validated['type']])
            ->with('success', $message);
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

    /**
     * Reglas de validación comunes para crear/actualizar productos.
     */
    private function productValidationRules(): array
    {
        $rules = [
            'type' => 'required|in:PRODUCT,INSUMO',
            'category_id' => 'required_if:type,PRODUCT|nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required_if:type,PRODUCT|nullable|numeric|min:0',
            'has_stock' => 'required|boolean',
            'stock_minimum' => 'required_if:has_stock,true|nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'unit' => 'required_if:type,INSUMO|nullable|string|max:50',
            'unit_cost' => 'required_if:type,INSUMO|nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ];

        if (Gate::allows('managePricing', Product::class)) {
            $rules['cost_price'] = 'nullable|required_with:profit_margin|numeric|min:0.01';
            $rules['profit_margin'] = 'nullable|numeric|min:0|max:100000';
            $rules['pricing_source'] = 'nullable|in:sale,margin';
        }

        return $rules;
    }

    /**
     * Aplica permisos de costo/margen y normaliza los datos de precio.
     */
    private function applyPricingPermissions(array $validated, ?Product $product = null): array
    {
        if (($validated['type'] ?? $product?->type) === 'INSUMO') {
            $validated['price'] = $validated['price'] ?? 0;
            $validated['cost_price'] = null;

            return $validated;
        }

        if (! Gate::allows('managePricing', Product::class)) {
            $validated['cost_price'] = $product?->cost_price;
            unset($validated['profit_margin'], $validated['pricing_source']);

            return $validated;
        }

        if (($validated['pricing_source'] ?? 'sale') === 'margin') {
            $validated = $this->pricing->apply($validated, $product?->getAttributes() ?? []);
        }

        unset($validated['profit_margin'], $validated['pricing_source']);

        return $validated;
    }
}


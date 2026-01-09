<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    /**
     * Mostrar lista de productos
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = Product::where('restaurant_id', $restaurantId)
            ->with('category');

        // Filtros
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->orderBy('name')->paginate(20);
        $categories = Category::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return view('products.index', compact('products', 'categories'));
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

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'has_stock' => 'boolean',
            'stock_minimum' => 'required_if:has_stock,1|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['has_stock'] = $request->has('has_stock');
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $product = Product::create($validated);

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

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'has_stock' => 'boolean',
            'stock_minimum' => 'required_if:has_stock,1|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['has_stock'] = $request->has('has_stock');
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $product->update($validated);

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

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Producto eliminado exitosamente');
    }
}


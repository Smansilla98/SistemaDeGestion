<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    /**
     * Listar productos
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = Product::where('restaurant_id', $restaurantId)
            ->with(['category']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $products = $query->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Mostrar un producto específico
     */
    public function show(Product $product)
    {
        if ($product->restaurant_id !== auth()->user()->restaurant_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $product->load(['category', 'modifiers']);

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    /**
     * Obtener productos por categoría
     */
    public function byCategory($categoryId)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $category = Category::where('id', $categoryId)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail();

        $products = Product::where('category_id', $categoryId)
            ->where('is_active', true)
            ->with(['modifiers'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
            'category' => $category
        ]);
    }
}


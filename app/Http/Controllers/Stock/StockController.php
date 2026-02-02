<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {
        $this->middleware('role:ADMIN,CAJERO');
    }

    /**
     * Mostrar control de stock
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = Product::where('restaurant_id', $restaurantId)
            ->where('has_stock', true)
            ->with(['category']);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->orderBy('name')->get();

        // Agregar stock actual a cada producto
        foreach ($products as $product) {
            $stock = Stock::where('restaurant_id', $restaurantId)
                ->where('product_id', $product->id)
                ->first();
            $product->current_stock = $stock ? $stock->quantity : 0;
            $product->is_low_stock = $product->current_stock <= $product->stock_minimum;
        }

        // Alertas de stock bajo
        $lowStockAlerts = $this->stockService->checkLowStock($restaurantId);

        return view('stock.index', compact('products', 'lowStockAlerts'));
    }

    /**
     * Mostrar movimientos de stock
     */
    public function movements(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = StockMovement::where('restaurant_id', $restaurantId)
            ->with(['product', 'user']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(50);

        $products = Product::where('restaurant_id', $restaurantId)
            ->where('has_stock', true)
            ->orderBy('name')
            ->get();

        return view('stock.movements', compact('movements', 'products'));
    }

    /**
     * Registrar movimiento de stock
     */
    public function storeMovement(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'type' => 'required|in:ENTRADA,SALIDA,AJUSTE',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255',
                'reference' => 'nullable|string|max:255',
            ]);

            $validated['restaurant_id'] = auth()->user()->restaurant_id;
            $validated['user_id'] = auth()->id();

            $this->stockService->recordMovement($validated);

            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Movimiento de stock registrado exitosamente'
                ]);
            }

            return back()->with('success', 'Movimiento de stock registrado exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Si es una petición AJAX, devolver errores de validación en JSON
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            // Si es una petición AJAX, devolver error en JSON
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar el movimiento: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }
}


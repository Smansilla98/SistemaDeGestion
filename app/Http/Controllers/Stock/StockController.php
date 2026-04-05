<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\StockService;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class StockController extends Controller
{
    use Auditable;

    public function __construct(
        private StockService $stockService
    ) {
        $this->middleware('role:ADMIN,GERENTE,CAJERO,MOZO')->except(['mozoInsumoCreate', 'mozoInsumoStore']);
        $this->middleware('role:MOZO,ADMIN,GERENTE,SUPERADMIN')->only(['mozoInsumoCreate', 'mozoInsumoStore']);
    }

    /**
     * Formulario simple: mozos registran entrada de stock solo para insumos (sin datos de compra).
     */
    public function mozoInsumoCreate()
    {
        if (! app(PermissionService::class)->allowed(auth()->user(), 'stock_mozo.create')) {
            abort(403, 'No tienes permiso para registrar ingreso de insumos');
        }

        $restaurantId = auth()->user()->restaurant_id;
        $products = Product::where('restaurant_id', $restaurantId)
            ->insumos()
            ->where('is_active', true)
            ->where('has_stock', true)
            ->orderBy('name')
            ->get();

        return view('stock.mozo-insumos', compact('products'));
    }

    /**
     * Guardar entrada de insumo (mozo): solo tipo INSUMO del mismo restaurante.
     */
    public function mozoInsumoStore(Request $request)
    {
        if (! app(PermissionService::class)->allowed(auth()->user(), 'stock_mozo.create')) {
            abort(403, 'No tienes permiso para registrar ingreso de insumos');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        if ($product->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403);
        }
        if (! $product->isInsumo()) {
            return back()->withInput()->with('error', 'Solo podés registrar ingresos de insumos.');
        }
        if (! $product->has_stock) {
            return back()->withInput()->with('error', 'Este insumo no tiene control de stock activado.');
        }

        try {
            $movement = $this->stockService->recordMovement([
                'restaurant_id' => auth()->user()->restaurant_id,
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'ENTRADA',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? 'Ingreso de insumo (mozo)',
            ]);

            $this->audit('STOCK_MOVEMENT_CREATED', StockMovement::class, $movement->id, [
                'type' => 'ENTRADA',
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'channel' => 'mozo_insumo',
            ]);

            return redirect()->route('stock.mozo-insumos.create')
                ->with('success', 'Ingreso registrado: '.$product->name.' (+'.(int) $validated['quantity'].').');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'No se pudo registrar el ingreso: '.$e->getMessage());
        }
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

        if (auth()->user()->role === User::ROLE_MOZO) {
            $query->products();
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->orderBy('name')->paginate(20)->withQueryString();

        // Agregar stock actual a cada producto
        foreach ($products as $product) {
            $stock = Stock::where('restaurant_id', $restaurantId)
                ->where('product_id', $product->id)
                ->first();
            $product->current_stock = $stock ? $stock->quantity : 0;
            $product->is_low_stock = $product->current_stock <= $product->stock_minimum;
        }

        $lowStockAlerts = $this->stockService->checkLowStock(
            $restaurantId,
            auth()->user()->role === User::ROLE_MOZO
        );

        return view('stock.index', compact('products', 'lowStockAlerts'));
    }

    /**
     * Mostrar movimientos de stock
     */
    public function movements(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = StockMovement::where('restaurant_id', $restaurantId)
            ->with(['product', 'user', 'purchase.supplier']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if (auth()->user()->role === User::ROLE_MOZO) {
            $query->whereHas('product', function ($q) {
                $q->where('type', 'PRODUCT');
            });
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(50);

        $productsQuery = Product::where('restaurant_id', $restaurantId)
            ->where('has_stock', true);
        if (auth()->user()->role === User::ROLE_MOZO) {
            $productsQuery->products();
        }
        $products = $productsQuery->orderBy('name')->get();

        return view('stock.movements', compact('movements', 'products'));
    }

    /**
     * Mostrar formulario de registro de movimiento
     */
    public function createMovement()
    {
        if (auth()->user()->role === User::ROLE_MOZO) {
            return redirect()->route('stock.index')
                ->with('info', 'Registrá movimientos con el botón «Movimiento» en cada producto.');
        }

        $restaurantId = auth()->user()->restaurant_id;

        $products = Product::where('restaurant_id', $restaurantId)
            ->where('has_stock', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stock.create-movement', compact('products', 'suppliers'));
    }

    /**
     * Registrar movimiento de stock (mejorado con compras)
     */
    public function storeMovement(Request $request)
    {
        try {
            // Validación base
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'type' => 'required|in:ENTRADA,SALIDA,AJUSTE',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255',
                'reference' => 'nullable|string|max:255',
            ]);

            $product = Product::findOrFail($validated['product_id']);
            if ($product->restaurant_id !== auth()->user()->restaurant_id) {
                abort(403);
            }
            if (auth()->user()->role === User::ROLE_MOZO) {
                if (! $product->isProduct()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'product_id' => ['Los mozos solo pueden mover stock de productos a la venta (no insumos).'],
                    ]);
                }
                if ($validated['type'] === 'AJUSTE') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'type' => ['Los mozos solo pueden registrar entradas o salidas.'],
                    ]);
                }
            }

            // ENTRADA con datos de compra (pantalla completa) vs entrada simple (modal / mozo)
            $wantsPurchaseFlow = $validated['type'] === 'ENTRADA'
                && (
                    $request->filled('supplier_id')
                    || $request->filled('new_supplier_name')
                    || $request->filled('unit_cost')
                    || $request->filled('purchase_date')
                );

            if ($validated['type'] === 'ENTRADA' && $wantsPurchaseFlow) {
                $purchaseValidation = $request->validate([
                    'supplier_id' => 'required_without:new_supplier_name|nullable|exists:suppliers,id',
                    'new_supplier_name' => 'required_without:supplier_id|nullable|string|max:255',
                    'unit_cost' => 'required|numeric|min:0',
                    'purchase_date' => 'required|date|before_or_equal:today',
                    'invoice_number' => 'nullable|string|max:255',
                    'purchase_notes' => 'nullable|string|max:500',
                ]);

                // Si se crea un nuevo proveedor
                if ($request->filled('new_supplier_name') && !$request->filled('supplier_id')) {
                    $supplier = Supplier::create([
                        'restaurant_id' => auth()->user()->restaurant_id,
                        'name' => $purchaseValidation['new_supplier_name'],
                        'contact_name' => $request->get('new_supplier_contact'),
                        'phone' => $request->get('new_supplier_phone'),
                        'email' => $request->get('new_supplier_email'),
                        'is_active' => true,
                    ]);
                    
                    // Auditoría
                    $this->auditCreate($supplier, $supplier->getAttributes());
                    
                    $purchaseValidation['supplier_id'] = $supplier->id;
                }

                // Agregar datos de compra
                $validated['purchase_data'] = [
                    'supplier_id' => $purchaseValidation['supplier_id'],
                    'unit_cost' => $purchaseValidation['unit_cost'],
                    'purchase_date' => $purchaseValidation['purchase_date'],
                    'invoice_number' => $purchaseValidation['invoice_number'] ?? null,
                    'notes' => $purchaseValidation['purchase_notes'] ?? null,
                ];
            }

            $validated['restaurant_id'] = auth()->user()->restaurant_id;
            $validated['user_id'] = auth()->id();

            $movement = $this->stockService->recordMovement($validated);
            
            // Auditoría
            $this->audit('STOCK_MOVEMENT_CREATED', StockMovement::class, $movement->id, [
                'type' => $validated['type'],
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
            ]);

            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $validated['type'] === 'ENTRADA' 
                        ? 'Entrada registrada correctamente. Stock actualizado.' 
                        : 'Movimiento de stock registrado exitosamente',
                    'movement' => $movement->load(['product', 'purchase.supplier']),
                ]);
            }

            return back()->with('success', 
                $validated['type'] === 'ENTRADA' 
                    ? 'Entrada registrada correctamente. Stock actualizado.' 
                    : 'Movimiento de stock registrado exitosamente'
            );
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


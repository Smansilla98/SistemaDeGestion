<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\Sector;
use App\Models\Order;
use App\Models\TableSession;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}
    /**
     * Mostrar lista de mesas
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with(['tables' => function ($query) {
                $query->orderBy('number')
                    ->with(['currentOrder']); // Eager loading de pedido actual
            }])
            ->get();

        // Productos activos para el modal de pedidos (mozos)
        $products = Product::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with(['category'])
            ->orderBy('name')
            ->get()
            ->groupBy('category.name');

        return view('tables.index', compact('sectors', 'products'));
    }

    /**
     * Crear un pedido desde el modal de mesas (AJAX)
     * POST /tables/{table}/orders
     */
    public function storeOrder(Request $request, Table $table)
    {
        // Solo ADMIN / MOZO
        if (!in_array(auth()->user()->role, ['ADMIN', 'MOZO'])) {
            abort(403, 'No tienes permisos para crear pedidos');
        }

        // Asegurar restaurante
        if ($table->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a esta mesa');
        }

        if ($table->status !== Table::STATUS_OCUPADA) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden tomar pedidos en mesas ocupadas.',
            ], 422);
        }

        if (!$table->current_session_id) {
            return response()->json([
                'success' => false,
                'message' => 'La mesa no tiene una sesión activa. Marcar como ocupada primero.',
            ], 422);
        }

        $validated = $request->validate([
            'observations' => 'nullable|string',
            'send_to_kitchen' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.observations' => 'nullable|string',
        ]);

        // Normalizar boolean
        $validated['send_to_kitchen'] = $request->boolean('send_to_kitchen');

        $data = [
            'restaurant_id' => auth()->user()->restaurant_id,
            'table_id' => $table->id,
            'user_id' => auth()->id(),
            'observations' => $validated['observations'] ?? null,
            'items' => $validated['items'],
        ];

        try {
            $order = $this->orderService->createOrder($data);

            foreach ($data['items'] as $itemData) {
                $this->orderService->addItem($order, $itemData);
            }

            if ($validated['send_to_kitchen']) {
                $this->orderService->sendToKitchen($order);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente.',
                'order_id' => $order->id,
                'order_number' => $order->number,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mostrar layout visual de mesas
     */
    public function layout(Request $request, ?int $sectorId = null)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->get();

        $selectedSector = null;
        $tables = collect();

        if ($sectorId) {
            $selectedSector = Sector::where('restaurant_id', $restaurantId)
                ->where('id', $sectorId)
                ->firstOrFail();

            $tables = Table::where('sector_id', $sectorId)
                ->get();
        }

        return view('tables.layout', compact('sectors', 'selectedSector', 'tables'));
    }

    /**
     * Crear nueva mesa
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Table::class);

        $validated = $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'number' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['status'] = 'LIBRE';

        $table = Table::create($validated);

        return redirect()->route('tables.index')
            ->with('success', 'Mesa creada exitosamente');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Table $table)
    {
        Gate::authorize('update', $table);

        $restaurantId = auth()->user()->restaurant_id;
        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->get();

        return view('tables.edit', compact('table', 'sectors'));
    }

    /**
     * Actualizar mesa
     */
    public function update(Request $request, Table $table)
    {
        Gate::authorize('update', $table);

        $validated = $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'number' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
            'status' => 'required|in:' . implode(',', Table::getStatuses()),
        ]);

        $table->update($validated);

        return redirect()->route('tables.index')
            ->with('success', 'Mesa actualizada exitosamente');
    }

    /**
     * Eliminar mesa
     */
    public function destroy(Table $table)
    {
        Gate::authorize('delete', $table);

        // Verificar que no tenga pedidos activos
        if ($table->current_order_id) {
            return back()->with('error', 'No se puede eliminar una mesa con pedido activo');
        }

        $table->delete();

        return redirect()->route('tables.index')
            ->with('success', 'Mesa eliminada exitosamente');
    }

    /**
     * Actualizar layout de mesas (posiciones)
     */
    public function updateLayout(Request $request)
    {
        // Verificar permisos: solo ADMIN y MOZO pueden actualizar layouts
        if (!in_array(auth()->user()->role, ['ADMIN', 'MOZO'])) {
            abort(403, 'No tienes permisos para actualizar el layout');
        }

        $validated = $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'tables' => 'required|array',
            'tables.*.id' => 'required|exists:tables,id',
            'tables.*.position_x' => 'required|integer|min:0',
            'tables.*.position_y' => 'required|integer|min:0',
            'fixtures' => 'nullable|array',
            'fixtures.*.id' => 'required_with:fixtures|string|max:50',
            'fixtures.*.position_x' => 'required_with:fixtures|integer|min:0',
            'fixtures.*.position_y' => 'required_with:fixtures|integer|min:0',
        ]);

        foreach ($validated['tables'] as $tableData) {
            $table = Table::find($tableData['id']);
            if ($table) {
                Gate::authorize('update', $table);
                $table->update([
                    'position_x' => $tableData['position_x'],
                    'position_y' => $tableData['position_y'],
                ]);
            }
        }

        // Guardar elementos fijos del sector (ej: escenario) en layout_config
        if (!empty($validated['fixtures'])) {
            $sector = Sector::find($validated['sector_id']);
            if ($sector) {
                $layoutConfig = is_array($sector->layout_config) ? $sector->layout_config : [];
                $layoutConfig['fixtures'] = $layoutConfig['fixtures'] ?? [];

                foreach ($validated['fixtures'] as $fixture) {
                    $layoutConfig['fixtures'][$fixture['id']] = [
                        'x' => (int) $fixture['position_x'],
                        'y' => (int) $fixture['position_y'],
                    ];
                }

                $sector->update(['layout_config' => $layoutConfig]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Layout actualizado exitosamente']);
    }

    /**
     * Actualizar estado de mesa con cantidad de personas
     */
    public function updateStatus(Request $request, Table $table)
    {
        Gate::authorize('update', $table);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', Table::getStatuses()),
            'guests_count' => 'nullable|integer|min:0|max:' . $table->capacity,
        ]);

        // Si el estado cambia a LIBRE, finalizar sesión y limpiar pedido actual
        if ($validated['status'] === 'LIBRE') {
            if ($table->current_session_id) {
                TableSession::where('id', $table->current_session_id)->update(['ended_at' => now()]);
            }
            $table->update([
                'status' => 'LIBRE',
                'current_order_id' => null,
                'current_session_id' => null,
            ]);
        } else {
            // Si pasa a OCUPADA y no hay sesión activa, crear una
            if ($validated['status'] === 'OCUPADA' && !$table->current_session_id) {
                // Fallback defensivo: si en prod aún no corrieron migraciones, evitar fatal
                if (!Schema::hasTable('table_sessions')) {
                    return redirect()->route('tables.index')
                        ->with('error', 'Faltan migraciones en la base de datos (table_sessions). Ejecutá migraciones para habilitar sesiones de mesa.');
                }
                
                try {
                    $session = TableSession::create([
                        'restaurant_id' => $table->restaurant_id,
                        'table_id' => $table->id,
                        'started_at' => now(),
                    ]);
                    $table->current_session_id = $session->id;
                } catch (\Exception $e) {
                    // Si falla la creación de sesión, registrar error pero permitir continuar
                    \Log::error('Error al crear sesión de mesa: ' . $e->getMessage());
                    return redirect()->route('tables.index')
                        ->with('error', 'Error al crear sesión de mesa. Verificá que las migraciones se hayan ejecutado correctamente. Error: ' . $e->getMessage());
                }
            }
            $table->update([
                'status' => $validated['status'],
                'current_session_id' => $table->current_session_id,
            ]);
        }

        return redirect()->route('tables.index')
            ->with('success', 'Estado de mesa actualizado exitosamente');
    }

    /**
     * Cerrar mesa: cierra todos los pedidos activos y genera recibo único consolidado
     */
    public function closeTable(Table $table)
    {
        Gate::authorize('update', $table);

        // Verificar que la mesa esté ocupada
        if ($table->status !== 'OCUPADA') {
            return back()->with('error', 'La mesa no está ocupada');
        }

        return DB::transaction(function () use ($table) {
            if (!$table->current_session_id) {
                return back()->with('error', 'La mesa no tiene una sesión activa para cerrar');
            }

            // Obtener todos los pedidos activos de la mesa (no cerrados)
            $activeOrders = Order::where('table_id', $table->id)
                ->where('table_session_id', $table->current_session_id)
                ->where('status', '!=', Order::STATUS_CERRADO)
                ->where('status', '!=', Order::STATUS_CANCELADO)
                ->with(['items.product'])
                ->get();

            if ($activeOrders->isEmpty()) {
                // Si no hay pedidos activos, solo liberar la mesa
                TableSession::where('id', $table->current_session_id)->update(['ended_at' => now()]);
                $table->update([
                    'status' => 'LIBRE',
                    'current_order_id' => null,
                    'current_session_id' => null,
                ]);

                return redirect()->route('tables.index')
                    ->with('success', 'Mesa liberada exitosamente');
            }

            // Cerrar todos los pedidos activos
            $totalAmount = 0;
            $ordersClosed = [];
            $allItems = collect();
            $totalSubtotal = 0;
            $totalDiscount = 0;

            foreach ($activeOrders as $order) {
                // Cargar items con productos
                $order->load(['items.product.category', 'items.modifiers']);
                
                // Cerrar el pedido sin liberar la mesa (lo haremos después)
                $this->orderService->closeOrder($order, false);
                
                $totalAmount += $order->total;
                $totalSubtotal += $order->subtotal;
                $totalDiscount += $order->discount;
                
                // Agrupar items por producto para consolidar
                // Se agrupan solo por product_id, sumando cantidades y subtotales
                foreach ($order->items as $item) {
                    // Buscar si ya existe un item con el mismo product_id
                    $existingItem = $allItems->first(function ($i) use ($item) {
                        return $i['product_id'] === $item->product_id;
                    });
                    
                    if ($existingItem) {
                        // Si existe, sumar cantidad y subtotal
                        $existingItem['quantity'] += $item->quantity;
                        $existingItem['subtotal'] += $item->subtotal;
                        // Recalcular precio unitario promedio
                        $existingItem['unit_price'] = $existingItem['subtotal'] / $existingItem['quantity'];
                    } else {
                        // Si no existe, agregar nuevo item
                        $allItems->push([
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                            'modifiers' => $item->modifiers,
                            'observations' => $item->observations,
                        ]);
                    }
                }
                
                $ordersClosed[] = $order;
            }

            // Liberar la mesa para nueva facturación
            TableSession::where('id', $table->current_session_id)->update(['ended_at' => now()]);
            $table->update([
                'status' => 'LIBRE',
                'current_order_id' => null,
                'current_session_id' => null,
            ]);

            // Redirigir a vista de recibo consolidado
            return redirect()->route('tables.consolidated-receipt', $table)
                ->with('success', 'Mesa cerrada exitosamente. Recibo consolidado generado.')
                ->with('table_session_id', $table->current_session_id)
                ->with('total_amount', $totalAmount)
                ->with('total_subtotal', $totalSubtotal)
                ->with('total_discount', $totalDiscount)
                ->with('orders_closed', $ordersClosed)
                ->with('consolidated_items', $allItems);
        });
    }

    /**
     * Mostrar resumen de cierre de mesa
     */
    public function closeSummary(Table $table)
    {
        Gate::authorize('view', $table);

        // Obtener los pedidos que se cerraron (los más recientes de esta mesa)
        $closedOrders = Order::where('table_id', $table->id)
            ->where('status', Order::STATUS_CERRADO)
            ->whereNotNull('closed_at')
            ->with(['items.product.category', 'items.modifiers', 'user', 'payments'])
            ->orderBy('closed_at', 'desc')
            ->limit(10) // Últimos 10 pedidos cerrados
            ->get();

        $totalAmount = $closedOrders->sum('total');

        return view('tables.close-summary', compact('table', 'closedOrders', 'totalAmount'));
    }

    /**
     * Mostrar recibo consolidado de todos los pedidos de la mesa
     */
    public function consolidatedReceipt(Table $table)
    {
        Gate::authorize('view', $table);

        // Obtener los pedidos cerrados más recientes (de la sesión actual)
        // Asegurarse de que siempre sea una colección
        $closedOrders = collect(session('orders_closed', []));
        $consolidatedItems = collect(session('consolidated_items', []));
        $totalAmount = session('total_amount', 0);
        $totalSubtotal = session('total_subtotal', 0);
        $totalDiscount = session('total_discount', 0);
        $sessionId = session('table_session_id');

        // Si no hay datos en sesión, obtener los últimos pedidos cerrados
        if ($closedOrders->isEmpty()) {
            $closedOrders = Order::where('table_id', $table->id)
                ->where('status', Order::STATUS_CERRADO)
                ->whereNotNull('closed_at')
                ->with(['items.product.category', 'items.modifiers', 'user', 'payments'])
                ->orderBy('closed_at', 'desc')
                ->limit(10)
                ->get();

            // Consolidar items
            // Se agrupan solo por product_id, sumando cantidades y subtotales
            $consolidatedItems = collect();
            foreach ($closedOrders as $order) {
                foreach ($order->items as $item) {
                    // Buscar si ya existe un item con el mismo product_id
                    $existingItem = $consolidatedItems->first(function ($i) use ($item) {
                        return $i['product_id'] === $item->product_id;
                    });
                    
                    if ($existingItem) {
                        // Si existe, sumar cantidad y subtotal
                        $existingItem['quantity'] += $item->quantity;
                        $existingItem['subtotal'] += $item->subtotal;
                        // Recalcular precio unitario promedio
                        $existingItem['unit_price'] = $existingItem['subtotal'] / $existingItem['quantity'];
                    } else {
                        // Si no existe, agregar nuevo item
                        $consolidatedItems->push([
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                            'modifiers' => $item->modifiers,
                            'observations' => $item->observations,
                        ]);
                    }
                }
            }
            
            $totalAmount = $closedOrders->sum('total');
            $totalSubtotal = $closedOrders->sum('subtotal');
            $totalDiscount = $closedOrders->sum('discount');
        }

        return view('tables.consolidated-receipt', compact(
            'table', 
            'closedOrders', 
            'consolidatedItems', 
            'totalAmount',
            'totalSubtotal',
            'totalDiscount'
        ));
    }

    /**
     * Mostrar pedidos de la sesión actual de una mesa (no histórico)
     */
    public function tableOrders(Table $table)
    {
        Gate::authorize('view', $table);

        $table->load('sector');

        $sessionId = $table->current_session_id;
        $orders = collect();

        if ($sessionId) {
            $orders = Order::where('table_id', $table->id)
                ->where('table_session_id', $sessionId)
                ->with(['items.product.category', 'items.modifiers', 'user', 'payments'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('tables.orders', compact('table', 'orders'));
    }

    // (el método "Mostrar todos los pedidos de una mesa" fue reemplazado por
    //  "Mostrar pedidos de la sesión actual de una mesa (no histórico)")
}


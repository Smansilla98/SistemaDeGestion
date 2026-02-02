<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\Sector;
use App\Models\Order;
use App\Models\TableSession;
use App\Models\Product;
use App\Models\Payment;
use App\Models\CashRegisterSession;
use App\Services\OrderService;
use App\Services\PrintService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class TableController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PrintService $printService,
        private StockService $stockService
    ) {}
    /**
     * Mostrar lista de mesas
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;
        $user = auth()->user();

        // Si es MOZO, solo ver mesas asignadas a él o libres
        $tablesQuery = Table::where('restaurant_id', $restaurantId);
        
        if ($user->role === 'MOZO') {
            $tablesQuery->where(function ($q) use ($user) {
                $q->where('status', 'LIBRE')
                  ->orWhereHas('currentSession', function ($sq) use ($user) {
                      $sq->where('waiter_id', $user->id)
                        ->where('status', TableSession::STATUS_ABIERTA);
                  });
            });
        }

        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with(['tables' => function ($query) use ($tablesQuery) {
                $query->orderBy('number')
                    ->with(['currentOrder', 'currentSession.waiter']); // Eager loading de pedido actual y sesión con mozo
            }])
            ->get();

        // Filtrar mesas por sector según el query del mozo
        if ($user->role === 'MOZO') {
            $allowedTableIds = $tablesQuery->pluck('id')->toArray();
            foreach ($sectors as $sector) {
                $sector->setRelation('tables', $sector->tables->filter(function ($table) use ($allowedTableIds) {
                    return in_array($table->id, $allowedTableIds);
                }));
            }
        }

        // Obtener mozos disponibles para asignación
        $waiters = \App\Models\User::where('restaurant_id', $restaurantId)
            ->where('role', 'MOZO')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Productos activos para el modal de pedidos (mozos)
        $products = Product::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with(['category'])
            ->orderBy('name')
            ->get()
            ->groupBy('category.name');

        return view('tables.index', compact('sectors', 'products', 'waiters'));
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

        // MÓDULO 2: Validar que la mesa tenga sesión ABIERTA
        if (!$table->current_session_id) {
            return response()->json([
                'success' => false,
                'message' => 'La mesa no tiene una sesión activa. Marcar como ocupada primero.',
            ], 422);
        }

        // Verificar que la sesión esté ABIERTA
        $session = TableSession::find($table->current_session_id);
        if (!$session || !$session->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'La sesión de la mesa no está abierta. No se pueden crear pedidos.',
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

            // Recargar el pedido con sus relaciones para la impresión
            $order->load(['table', 'items.product', 'items.modifiers']);

            // Imprimir automáticamente la comanda de cocina
            try {
                $printer = $this->printService->getPrinterByType($order->restaurant_id, 'kitchen');
                $this->printService->printKitchenTicket($order, $printer);
                $printMessage = 'La comanda de cocina se ha impreso automáticamente.';
            } catch (\Exception $printError) {
                // Si falla la impresión, no fallar el pedido, solo registrar el error
                Log::warning('Error al imprimir comanda automáticamente: ' . $printError->getMessage(), [
                    'order_id' => $order->id,
                    'order_number' => $order->number
                ]);
                $printMessage = 'Pedido creado. La comanda está disponible para imprimir manualmente.';
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente. ' . $printMessage,
                'order_id' => $order->id,
                'order_number' => $order->number,
                'kitchen_ticket_url' => route('orders.print.kitchen', $order),
                'comanda_url' => route('orders.print.comanda', $order),
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
            
            // Cargar subsectores con sus items
            $selectedSector->load(['subsectors.items' => function($query) {
                $query->orderBy('position');
            }]);
        }

        // Obtener mozos para el modal de cambio de estado
        $waiters = \App\Models\User::where('restaurant_id', $restaurantId)
            ->where('role', 'MOZO')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Obtener productos para el modal de pedidos
        $products = \App\Models\Product::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with(['category'])
            ->orderBy('name')
            ->get()
            ->groupBy('category.name');

        return view('tables.layout', compact('sectors', 'selectedSector', 'tables', 'waiters', 'products'));
    }

    /**
     * Crear pedido desde un item de subsector
     */
    public function storeOrderFromSubsectorItem(Request $request, \App\Models\SubsectorItem $item)
    {
        // Solo ADMIN / MOZO
        if (!in_array(auth()->user()->role, ['ADMIN', 'MOZO'])) {
            abort(403, 'No tienes permisos para crear pedidos');
        }

        // Asegurar restaurante
        if ($item->subsector->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a este elemento');
        }

        if ($item->status !== \App\Models\SubsectorItem::STATUS_OCUPADA && $item->status !== \App\Models\SubsectorItem::STATUS_LIBRE) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden tomar pedidos en elementos libres u ocupados.',
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
            'subsector_item_id' => $item->id,
            'user_id' => auth()->id(),
            'observations' => $validated['observations'] ?? null,
            'items' => $validated['items'],
        ];

        try {
            $order = $this->orderService->createOrder($data);

            foreach ($data['items'] as $itemData) {
                $this->orderService->addItem($order, $itemData);
            }

            // Recargar el pedido con sus relaciones para la impresión
            $order->load(['subsectorItem.subsector', 'items.product', 'items.modifiers']);

            // Imprimir automáticamente la comanda de cocina
            try {
                $printer = $this->printService->getPrinterByType($order->restaurant_id, 'kitchen');
                $this->printService->printKitchenTicket($order, $printer);
                $printMessage = 'La comanda de cocina se ha impreso automáticamente.';
            } catch (\Exception $printError) {
                Log::warning('Error al imprimir comanda automáticamente: ' . $printError->getMessage(), [
                    'order_id' => $order->id,
                    'order_number' => $order->number
                ]);
                $printMessage = 'Pedido creado. La comanda está disponible para imprimir manualmente.';
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente. ' . $printMessage,
                'order_id' => $order->id,
                'order_number' => $order->number,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage(),
            ], 500);
        }
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

        // Verificar que no tenga sesión activa
        if ($table->current_session_id) {
            $session = TableSession::find($table->current_session_id);
            if ($session && $session->isOpen()) {
                return back()->with('error', 'No se puede eliminar una mesa con sesión abierta. Cerrá la sesión primero.');
            }
        }

        // Verificar que no tenga pedidos asociados (históricos)
        $hasOrders = Order::where('table_id', $table->id)->exists();
        if ($hasOrders) {
            // Aunque tenga pedidos históricos, permitir eliminación pero advertir
            // (opcional: podrías requerir confirmación adicional aquí)
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
            'waiter_id' => 'required_if:status,OCUPADA|nullable|exists:users,id', // OBLIGATORIO si pasa a OCUPADA
        ]);

        // Si el estado cambia a LIBRE, finalizar sesión y limpiar pedido actual
        if ($validated['status'] === 'LIBRE') {
            if ($table->current_session_id) {
                DB::table('table_sessions')
                    ->where('id', $table->current_session_id)
                    ->update([
                        'ended_at' => now(),
                        'status' => 'CERRADA', // Usar string directo
                        'updated_at' => now(),
                    ]);
            }
            $table->update([
                'status' => 'LIBRE',
                'current_order_id' => null,
                'current_session_id' => null,
            ]);
        } else {
            // Si pasa a OCUPADA y no hay sesión activa, crear una
            if ($validated['status'] === 'OCUPADA' && !$table->current_session_id) {
                // Requerir waiter_id al abrir mesa
                if (empty($validated['waiter_id'])) {
                    return redirect()->route('tables.index')
                        ->with('error', 'Debes asignar un mozo al abrir la mesa.');
                }
                
                // Verificar que el waiter_id sea un MOZO del mismo restaurante
                $waiter = \App\Models\User::where('id', $validated['waiter_id'])
                    ->where('restaurant_id', $table->restaurant_id)
                    ->where('role', 'MOZO')
                    ->where('is_active', true)
                    ->first();
                
                if (!$waiter) {
                    return redirect()->route('tables.index')
                        ->with('error', 'El mozo seleccionado no es válido o no pertenece a este restaurante.');
                }
                
                // Fallback defensivo: si en prod aún no corrieron migraciones, evitar fatal
                if (!Schema::hasTable('table_sessions')) {
                    return redirect()->route('tables.index')
                        ->with('error', 'Faltan migraciones en la base de datos (table_sessions). Ejecutá migraciones para habilitar sesiones de mesa.');
                }
                
                try {
                    $session = TableSession::create([
                        'restaurant_id' => $table->restaurant_id,
                        'table_id' => $table->id,
                        'waiter_id' => $validated['waiter_id'],
                        'opened_by_user_id' => auth()->id(),
                        'started_at' => now(),
                        'status' => TableSession::STATUS_ABIERTA,
                    ]);
                    $table->current_session_id = $session->id;
                } catch (\Exception $e) {
                    // Si falla la creación de sesión, registrar error pero permitir continuar
                    Log::error('Error al crear sesión de mesa: ' . $e->getMessage());
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
     * Mostrar resumen y modal de pago antes de cerrar mesa
     */
    public function showCloseTable(Table $table)
    {
        Gate::authorize('update', $table);

        if ($table->status !== 'OCUPADA') {
            return back()->with('error', 'La mesa no está ocupada');
        }

        if (!$table->current_session_id) {
            return back()->with('error', 'La mesa no tiene una sesión activa para cerrar');
        }

        // Obtener todos los pedidos activos de la mesa
        $activeOrders = Order::where('table_id', $table->id)
            ->where('table_session_id', $table->current_session_id)
            ->where('status', '!=', Order::STATUS_CERRADO)
            ->where('status', '!=', Order::STATUS_CANCELADO)
            ->with(['items.product.category', 'items.modifiers'])
            ->get();

        if ($activeOrders->isEmpty()) {
            // Si no hay pedidos, solo liberar la mesa
                DB::table('table_sessions')
                    ->where('id', $table->current_session_id)
                    ->update([
                        'ended_at' => now(),
                        'status' => 'CERRADA', // Usar string directo
                        'updated_at' => now(),
                    ]);
            $table->update([
                'status' => 'LIBRE',
                'current_order_id' => null,
                'current_session_id' => null,
            ]);

            return redirect()->route('tables.index')
                ->with('success', 'Mesa liberada exitosamente');
        }

        // Calcular totales
        $totalAmount = 0;
        $totalSubtotal = 0;
        $totalDiscount = 0;
        $allItems = collect();

        foreach ($activeOrders as $order) {
            $order->load(['items.product.category', 'items.modifiers']);
            $totalAmount += $order->total;
            $totalSubtotal += $order->subtotal;
            $totalDiscount += $order->discount;

            foreach ($order->items as $item) {
                $existingItem = $allItems->first(function ($i) use ($item) {
                    return $i['product_id'] === $item->product_id;
                });

                if ($existingItem) {
                    $existingItem['quantity'] += $item->quantity;
                    $existingItem['subtotal'] += $item->subtotal;
                    $existingItem['unit_price'] = $existingItem['subtotal'] / $existingItem['quantity'];
                } else {
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
        }

        $table->load('currentSession.waiter', 'sector');

        return view('tables.close-payment', compact('table', 'activeOrders', 'totalAmount', 'totalSubtotal', 'totalDiscount', 'allItems'));
    }

    /**
     * Procesar pago y cerrar mesa
     */
    public function processPayment(Request $request, Table $table)
    {
        Gate::authorize('update', $table);

        // Helper para devolver respuesta JSON o HTML según el tipo de petición
        $respond = function ($success, $message, $data = null) use ($request) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => $success,
                    'message' => $message,
                    'data' => $data
                ], $success ? 200 : 422);
            }
            return $success 
                ? redirect()->back()->with('success', $message)
                : redirect()->back()->with('error', $message)->withInput();
        };

        if ($table->status !== 'OCUPADA') {
            return $respond(false, 'La mesa no está ocupada');
        }

        if (!$table->current_session_id) {
            return $respond(false, 'La mesa no tiene una sesión activa');
        }

        try {
            // MÓDULO 4: Validación de métodos de pago incluyendo QR y MIXTO
            $validated = $request->validate([
                'payments' => 'required|array|min:1',
                'payments.*.payment_method' => 'required|in:EFECTIVO,DEBITO,CREDITO,TRANSFERENCIA,QR,MIXTO',
                'payments.*.amount' => 'required|numeric|min:0.01',
                'payments.*.operation_number' => 'nullable|string|max:255',
                'payments.*.notes' => 'nullable|string|max:500',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        try {
            return DB::transaction(function () use ($table, $validated, $request, $respond) {
            // Obtener todos los pedidos activos
            $activeOrders = Order::where('table_id', $table->id)
                ->where('table_session_id', $table->current_session_id)
                ->where('status', '!=', Order::STATUS_CERRADO)
                ->where('status', '!=', Order::STATUS_CANCELADO)
                ->with(['items.product'])
                ->get();

            if ($activeOrders->isEmpty()) {
                return $respond(false, 'No hay pedidos activos para cerrar');
            }

            // Calcular total
            $totalAmount = $activeOrders->sum('total');
            $totalPaid = collect($validated['payments'])->sum('amount');

            // Validar que el total pagado sea igual al total
            if (abs($totalPaid - $totalAmount) > 0.01) {
                return $respond(false, "El total pagado ($${totalPaid}) no coincide con el total a pagar ($${totalAmount})");
            }

            // Cerrar todos los pedidos
            $ordersClosed = [];
            $allItems = collect();
            $totalSubtotal = 0;
            $totalDiscount = 0;

            foreach ($activeOrders as $order) {
                $order->load(['items.product.category', 'items.modifiers']);
                $this->orderService->closeOrder($order, false);
                
                $totalSubtotal += $order->subtotal;
                $totalDiscount += $order->discount;
                
                foreach ($order->items as $item) {
                    $existingItem = $allItems->first(function ($i) use ($item) {
                        return $i['product_id'] === $item->product_id;
                    });
                    
                    if ($existingItem) {
                        $existingItem['quantity'] += $item->quantity;
                        $existingItem['subtotal'] += $item->subtotal;
                        $existingItem['unit_price'] = $existingItem['subtotal'] / $existingItem['quantity'];
                    } else {
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

            // Verificar stock después de cerrar los pedidos
            // Recorrer todos los items de todos los pedidos para verificar stock
            $stockVerificationErrors = [];
            foreach ($ordersClosed as $order) {
                foreach ($order->items as $item) {
                    if ($item->product->has_stock) {
                        $currentStock = $item->product->getCurrentStock($order->restaurant_id);
                        $expectedStock = $item->product->getCurrentStock($order->restaurant_id);
                        
                        // Verificar que el stock se haya reducido correctamente
                        // El stock debería haberse reducido cuando se agregó el item al pedido
                        // Aquí solo verificamos que no haya inconsistencias
                        if ($currentStock < 0) {
                            $stockVerificationErrors[] = "Stock negativo detectado para '{$item->product->name}': {$currentStock}";
                        }
                    }
                }
            }
            
            // Si hay errores de stock, registrar en logs pero no bloquear el cierre
            if (!empty($stockVerificationErrors)) {
                Log::warning('Errores de verificación de stock al cerrar mesa', [
                    'table_id' => $table->id,
                    'restaurant_id' => $table->restaurant_id,
                    'errors' => $stockVerificationErrors,
                    'user_id' => auth()->id()
                ]);
            }

            // Crear pagos consolidados por sesión de mesa
            // Asociar cada pago al primer pedido (para mantener relación con order_id) pero con table_session_id
            $firstOrder = !empty($ordersClosed) ? $ordersClosed[0] : null;
            if (!$firstOrder) {
                return $respond(false, 'No se pudo obtener el pedido para asociar el pago');
            }
            
            // Obtener la sesión de caja activa del restaurante
            $cashRegisterSession = CashRegisterSession::where('restaurant_id', $table->restaurant_id)
                ->where('status', CashRegisterSession::STATUS_ABIERTA)
                ->orderBy('opened_at', 'desc')
                ->first();
            
            // Si no hay sesión de caja activa, registrar en logs pero continuar
            if (!$cashRegisterSession) {
                Log::warning('No se encontró sesión de caja activa al procesar pago', [
                    'table_id' => $table->id,
                    'restaurant_id' => $table->restaurant_id,
                    'user_id' => auth()->id()
                ]);
            }
            
            // Obtener información del mozo (del primer pedido)
            $waiterName = $firstOrder->user->name ?? 'N/A';
            
            $paymentsCreated = [];
            foreach ($validated['payments'] as $paymentData) {
                // Construir notas con información de mesa y mozo
                $notes = $paymentData['notes'] ?? '';
                $additionalInfo = "Mesa: {$table->number} | Mozo: {$waiterName}";
                if ($notes) {
                    $notes = "{$additionalInfo} | {$notes}";
                } else {
                    $notes = $additionalInfo;
                }
                
                $payment = Payment::create([
                    'restaurant_id' => $table->restaurant_id,
                    'order_id' => $firstOrder->id, // Asociar al primer pedido para mantener relación
                    'table_session_id' => $table->current_session_id, // Asociar a la sesión de mesa para arqueo
                    'cash_register_session_id' => $cashRegisterSession->id ?? null, // Asociar a la sesión de caja
                    'user_id' => auth()->id(),
                    'payment_method' => $paymentData['payment_method'],
                    'amount' => $paymentData['amount'],
                    'operation_number' => $paymentData['operation_number'] ?? null,
                    'notes' => $notes,
                ]);
                $paymentsCreated[] = $payment;
            }

            // Liberar la mesa
            // Usar DB::statement para asegurar que el enum se actualice correctamente
            DB::table('table_sessions')
                ->where('id', $table->current_session_id)
                ->update([
                    'ended_at' => now(),
                    'status' => 'CERRADA', // Usar string directo para evitar problemas con el enum
                    'updated_at' => now(),
                ]);
            $table->update([
                'status' => 'LIBRE',
                'current_order_id' => null,
                'current_session_id' => null,
            ]);

            // Guardar el table_session_id antes de limpiarlo
            $savedSessionId = $table->current_session_id;
            
            // Si es petición AJAX, devolver JSON
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mesa cerrada y pago procesado exitosamente.',
                    'redirect' => route('tables.consolidated-receipt', $table)
                ]);
            }

            return redirect()->route('tables.consolidated-receipt', $table)
                ->with('success', 'Mesa cerrada y pago procesado exitosamente.')
                ->with('table_session_id', $savedSessionId) // Usar el ID guardado antes de limpiarlo
                ->with('total_amount', $totalAmount)
                ->with('total_subtotal', $totalSubtotal)
                ->with('total_discount', $totalDiscount)
                ->with('orders_closed', $ordersClosed)
                ->with('consolidated_items', $allItems)
                ->with('payments', collect($paymentsCreated));
            });
        } catch (\Exception $e) {
            Log::error('Error al procesar pago: ' . $e->getMessage(), [
                'table_id' => $table->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ocurrió un error al procesar el pago: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Ocurrió un error al procesar el pago. Por favor, intenta nuevamente.')
                ->withInput();
        }
    }

    /**
     * Cerrar mesa: cierra todos los pedidos activos y genera recibo único consolidado
     * @deprecated Usar showCloseTable y processPayment en su lugar
     */
    public function closeTable(Table $table)
    {
        // Redirigir al nuevo flujo de pago
        return redirect()->route('tables.show-close', $table);
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
        $payments = collect(session('payments', []));
        
        // Si hay sessionId, obtener pagos de la base de datos
        if ($sessionId) {
            $dbPayments = Payment::where('table_session_id', $sessionId)
                ->with('user')
                ->get();
            if ($dbPayments->isNotEmpty()) {
                $payments = $dbPayments;
            }
        }

        // Si no hay datos en sesión, intentar obtenerlos de la base de datos
        if ($closedOrders->isEmpty()) {
            // Si no hay sessionId en sesión, intentar obtenerlo de los pagos más recientes
            if (!$sessionId) {
                $recentPayment = Payment::where('restaurant_id', $table->restaurant_id)
                    ->whereHas('order', function($query) use ($table) {
                        $query->where('table_id', $table->id);
                    })
                    ->whereNotNull('table_session_id')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($recentPayment) {
                    $sessionId = $recentPayment->table_session_id;
                } else {
                    // Si no hay pagos, obtener la última sesión cerrada de la mesa
                    $lastSession = \App\Models\TableSession::where('table_id', $table->id)
                        ->where('status', \App\Models\TableSession::STATUS_CERRADA)
                        ->orderBy('ended_at', 'desc')
                        ->first();
                    
                    if ($lastSession) {
                        $sessionId = $lastSession->id;
                    }
                }
            }
            
            // Obtener pedidos cerrados filtrando por table_session_id si está disponible
            $query = Order::where('table_id', $table->id)
                ->where('status', Order::STATUS_CERRADO)
                ->whereNotNull('closed_at');
            
            // CRÍTICO: Filtrar por table_session_id si está disponible
            if ($sessionId) {
                $query->where('table_session_id', $sessionId);
            }
            
            $closedOrders = $query->with(['items.product.category', 'items.modifiers', 'user', 'payments'])
                ->orderBy('closed_at', 'desc')
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
            'totalDiscount',
            'payments'
        ));
    }

    /**
     * Imprimir recibo consolidado (formato ticket térmico 80mm)
     */
    public function printConsolidatedReceipt(Table $table)
    {
        Gate::authorize('view', $table);

        // Obtener los datos de la sesión o de la base de datos
        $closedOrders = collect(session('orders_closed', []));
        $consolidatedItems = collect(session('consolidated_items', []));
        $totalAmount = session('total_amount', 0);
        $totalSubtotal = session('total_subtotal', 0);
        $totalDiscount = session('total_discount', 0);
        $sessionId = session('table_session_id');
        $payments = collect(session('payments', []));

        // Si no hay datos en sesión o están vacíos, obtenerlos de la base de datos
        if (($closedOrders->isEmpty() || $consolidatedItems->isEmpty()) && $sessionId) {
            $closedOrders = Order::where('table_id', $table->id)
                ->where('table_session_id', $sessionId)
                ->where('status', Order::STATUS_CERRADO)
                ->with(['items.product', 'user'])
                ->get();

            if ($closedOrders->isNotEmpty()) {
                $consolidatedItems = collect();
                foreach ($closedOrders as $order) {
                    foreach ($order->items as $item) {
                        $existingItem = $consolidatedItems->first(function ($i) use ($item) {
                            return $i['product_id'] === $item->product_id;
                        });
                        
                        if ($existingItem) {
                            $existingItem['quantity'] += $item->quantity;
                            $existingItem['subtotal'] += $item->subtotal;
                            $existingItem['unit_price'] = $existingItem['subtotal'] / $existingItem['quantity'];
                        } else {
                            $consolidatedItems->push([
                                'product_id' => $item->product_id,
                                'product_name' => $item->product->name,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'subtotal' => $item->subtotal,
                            ]);
                        }
                    }
                }
                
                $totalAmount = $closedOrders->sum('total');
                $totalSubtotal = $closedOrders->sum('subtotal');
                $totalDiscount = $closedOrders->sum('discount');
            }
        }
        
        // Si aún no hay sessionId, intentar obtener los pedidos más recientes cerrados de esta mesa
        if (($closedOrders->isEmpty() || $consolidatedItems->isEmpty()) && !$sessionId) {
            $closedOrders = Order::where('table_id', $table->id)
                ->where('status', Order::STATUS_CERRADO)
                ->whereNotNull('closed_at')
                ->with(['items.product', 'user'])
                ->orderBy('closed_at', 'desc')
                ->limit(10)
                ->get();

            if ($closedOrders->isNotEmpty()) {
                $consolidatedItems = collect();
                foreach ($closedOrders as $order) {
                    foreach ($order->items as $item) {
                        $existingItem = $consolidatedItems->first(function ($i) use ($item) {
                            return $i['product_id'] === $item->product_id;
                        });
                        
                        if ($existingItem) {
                            $existingItem['quantity'] += $item->quantity;
                            $existingItem['subtotal'] += $item->subtotal;
                            $existingItem['unit_price'] = $existingItem['subtotal'] / $existingItem['quantity'];
                        } else {
                            $consolidatedItems->push([
                                'product_id' => $item->product_id,
                                'product_name' => $item->product->name,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'subtotal' => $item->subtotal,
                            ]);
                        }
                    }
                }
                
                $totalAmount = $closedOrders->sum('total');
                $totalSubtotal = $closedOrders->sum('subtotal');
                $totalDiscount = $closedOrders->sum('discount');
            }
        }

        // Si los totales están en 0, calcularlos desde los items consolidados
        if ($totalSubtotal == 0 && $consolidatedItems->isNotEmpty()) {
            $totalSubtotal = $consolidatedItems->sum('subtotal');
        }
        
        if ($totalAmount == 0 && $totalSubtotal > 0) {
            $totalAmount = $totalSubtotal - $totalDiscount;
        }

        // Obtener pagos de la base de datos si hay sessionId
        if ($sessionId && $payments->isEmpty()) {
            $dbPayments = Payment::where('table_session_id', $sessionId)
                ->with('user')
                ->get();
            if ($dbPayments->isNotEmpty()) {
                $payments = $dbPayments;
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('tables.print-consolidated-receipt', compact(
            'table',
            'closedOrders',
            'consolidatedItems',
            'totalAmount',
            'totalSubtotal',
            'totalDiscount',
            'payments'
        ))
            ->setPaper([0, 0, 226.77, 841.89], 'portrait') // 80mm de ancho
            ->setOption('enable-local-file-access', true);

        return $pdf->stream("recibo-consolidado-mesa-{$table->number}.pdf");
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



<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Table;
use App\Models\Product;
use App\Models\Category;
use App\Models\Payment;
use App\Models\CashRegisterSession;
use App\Services\OrderService;
use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PrintService $printService
    ) {}

    /**
     * Mostrar lista de pedidos
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = Order::where('restaurant_id', $restaurantId)
            ->with(['table', 'user', 'items.product.category']);

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('table_id')) {
            $query->where('table_id', $request->table_id);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    /**
     * Mostrar formulario de creación de pedido
     */
    public function create(Request $request, ?int $tableId = null)
    {
        Gate::authorize('create', Order::class);

        $restaurantId = auth()->user()->restaurant_id;

        // Si se especifica una mesa, verificar que esté OCUPADA
        if ($tableId) {
            $selectedTable = Table::findOrFail($tableId);
            
            // Verificar que la mesa pertenezca al restaurante del usuario
            if ($selectedTable->restaurant_id !== $restaurantId) {
                abort(403, 'No tienes acceso a esta mesa');
            }
            
            // Verificar que la mesa esté OCUPADA para poder tomar pedidos
            if ($selectedTable->status !== 'OCUPADA') {
                return redirect()->route('tables.index')
                    ->with('error', 'Solo se pueden tomar pedidos en mesas ocupadas. Por favor, cambia el estado de la mesa a OCUPADA primero.');
            }
        }

        // Solo mostrar mesas OCUPADAS para seleccionar
        $tables = Table::where('restaurant_id', $restaurantId)
            ->where('status', 'OCUPADA')
            ->get();

        $products = Product::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with(['category', 'modifiers'])
            ->get()
            ->groupBy('category.name');

        $selectedTable = $tableId ? Table::find($tableId) : null;

        return view('orders.create', compact('tables', 'products', 'selectedTable'));
    }

    /**
     * Crear nuevo pedido
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Order::class);

        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'observations' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Verificar que la mesa esté OCUPADA para poder crear pedidos
        $table = Table::findOrFail($validated['table_id']);
        if ($table->status !== 'OCUPADA') {
            return back()->with('error', 'Solo se pueden tomar pedidos en mesas ocupadas. Por favor, cambia el estado de la mesa a OCUPADA primero.')
                ->withInput();
        }

        // Verificar que la mesa pertenezca al restaurante del usuario
        if ($table->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a esta mesa');
        }

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['user_id'] = auth()->id();

        try {
            $order = $this->orderService->createOrder($validated);

            // Agregar items al pedido
            foreach ($validated['items'] as $itemData) {
                try {
                    $this->orderService->addItem($order, $itemData);
                } catch (\Exception $e) {
                    // Si es un error de stock, retornar con el mensaje
                    if (str_contains($e->getMessage(), 'Stock insuficiente')) {
                        return back()
                            ->with('error', $e->getMessage())
                            ->withInput();
                    }
                    // Re-lanzar otras excepciones
                    throw $e;
                }
            }

            return redirect()->route('orders.show', $order)
                ->with('success', 'Pedido creado exitosamente');
        } catch (\Exception $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar pedido
     */
    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load(['table', 'user', 'items.product.category', 'items.modifiers', 'payments']);

        return view('orders.show', compact('order'));
    }

    /**
     * Agregar item al pedido
     */
    public function addItem(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'observations' => 'nullable|string',
            'modifiers' => 'nullable|array',
            'modifiers.*' => 'exists:product_modifiers,id',
        ]);

        try {
            $this->orderService->addItem($order, $validated);

            return back()->with('success', 'Item agregado al pedido');
        } catch (\Exception $e) {
            // Si es un error de stock, retornar con el mensaje
            if (str_contains($e->getMessage(), 'Stock insuficiente')) {
                return back()
                    ->with('error', $e->getMessage())
                    ->withInput();
            }
            // Re-lanzar otras excepciones
            throw $e;
        }
    }

    /**
     * Enviar pedido a cocina
     */
    public function sendToKitchen(Order $order)
    {
        Gate::authorize('update', $order);

        $this->orderService->sendToKitchen($order);

        return back()->with('success', 'Pedido enviado a cocina');
    }

    /**
     * Cerrar pedido y mostrar resumen
     */
    public function close(Order $order)
    {
        Gate::authorize('update', $order);

        // Cerrar el pedido
        $this->orderService->closeOrder($order);

        // Redirigir al resumen
        return redirect()->route('orders.summary', $order)
            ->with('success', 'Pedido cerrado exitosamente. Aquí está el resumen para el cliente.');
    }

    /**
     * Mostrar resumen del pedido para el cliente
     */
    public function summary(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load([
            'restaurant',
            'table',
            'user',
            'items.product.category',
            'items.modifiers',
            'payments'
        ]);

        return view('orders.summary', compact('order'));
    }

    /**
     * Cambiar estado del pedido (simplificado: solo mozo puede cambiar)
     * Flujo: ABIERTO -> EN_PREPARACION -> ENTREGADO
     */
    public function updateStatus(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $validated = $request->validate([
            'status' => 'required|in:EN_PREPARACION,ENTREGADO'
        ]);

        $newStatus = $validated['status'];
        $currentStatus = $order->status;

        // Validar transiciones permitidas
        $allowedTransitions = [
            'ABIERTO' => ['EN_PREPARACION'],
            'EN_PREPARACION' => ['ENTREGADO'],
        ];

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return back()->with('error', "No se puede cambiar el estado de {$currentStatus} a {$newStatus}");
        }

        // Actualizar estado
        $order->status = $newStatus;
        
        if ($newStatus === 'EN_PREPARACION' && !$order->sent_at) {
            $order->sent_at = now();
        }
        
        if ($newStatus === 'ENTREGADO') {
            // MÓDULO 2: Notificar que el pedido fue entregado
            // La notificación se mostrará en la vista mediante JavaScript
            $order->load(['table']);
        }
        
        $order->save();

        // MÓDULO 2: Mensaje especial para pedidos entregados
        if ($newStatus === 'ENTREGADO') {
            return back()->with('success', "✅ Pedido #{$order->number} entregado en Mesa {$order->table->number}")
                         ->with('order_delivered', [
                             'order_number' => $order->number,
                             'table_number' => $order->table->number
                         ]);
        }

        return back()->with('success', "Estado del pedido actualizado a {$newStatus}");
    }

    /**
     * Eliminar pedido
     * Solo ADMIN puede eliminar pedidos en estado ABIERTO o CANCELADO
     */
    public function destroy(Order $order)
    {
        Gate::authorize('delete', $order);

        // Verificar que el pedido esté en un estado que permita eliminación
        if (!in_array($order->status, ['ABIERTO', 'CANCELADO'])) {
            return back()->with('error', 'Solo se pueden eliminar pedidos en estado ABIERTO o CANCELADO');
        }

        // Verificar que no tenga pagos asociados
        if ($order->payments()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un pedido que tiene pagos asociados');
        }

        try {
            // Eliminar items primero (si hay restricciones de foreign key)
            $order->items()->delete();
            
            // Eliminar el pedido
            $order->delete();

            return redirect()->route('orders.index')
                ->with('success', 'Pedido eliminado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el pedido: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de pedido rápido (consumo inmediato)
     */
    public function quickOrder()
    {
        Gate::authorize('create', Order::class);
        
        $restaurantId = auth()->user()->restaurant_id;
        
        // Obtener sesión de caja activa
        $activeSession = CashRegisterSession::where('restaurant_id', $restaurantId)
            ->where('status', 'ABIERTA')
            ->first();

        if (!$activeSession) {
            return redirect()->route('cash-register.index')
                ->with('error', 'Debes tener una sesión de caja abierta para realizar pedidos rápidos');
        }

        // Obtener productos vendibles (no insumos)
        $products = Product::where('restaurant_id', $restaurantId)
            ->where('type', 'PRODUCT')
            ->where('is_active', true)
            ->with('category')
            ->orderBy('name')
            ->get();

        // Agrupar por categoría
        $categories = Category::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with(['products' => function($query) use ($restaurantId) {
                $query->where('restaurant_id', $restaurantId)
                    ->where('type', 'PRODUCT')
                    ->where('is_active', true);
            }])
            ->orderBy('display_order')
            ->get();

        return view('orders.quick-order', compact('activeSession', 'products', 'categories'));
    }

    /**
     * Crear y procesar pedido rápido (consumo inmediato)
     */
    public function processQuickOrder(Request $request)
    {
        Gate::authorize('create', Order::class);

        $restaurantId = auth()->user()->restaurant_id;
        
        $validated = $request->validate([
            'cash_register_session_id' => 'required|exists:cash_register_sessions,id',
            'customer_name' => 'required|string|min:2|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'exists:products,id',
                function ($attribute, $value, $fail) use ($restaurantId) {
                    $product = \App\Models\Product::find($value);
                    if ($product && $product->restaurant_id !== $restaurantId) {
                        $fail('El producto seleccionado no pertenece a este restaurante.');
                    }
                    if ($product && !$product->is_active) {
                        $fail('El producto seleccionado no está activo.');
                    }
                },
            ],
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.observations' => 'nullable|string|max:500',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method' => 'required|in:EFECTIVO,DEBITO,CREDITO,TRANSFERENCIA,QR,MIXTO',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.operation_number' => 'nullable|string|max:255',
            'payments.*.notes' => 'nullable|string|max:500',
            'print_ticket' => 'nullable|boolean',
        ]);

        try {
            Log::info('Iniciando procesamiento de pedido rápido', [
                'user_id' => auth()->id(),
                'restaurant_id' => auth()->user()->restaurant_id,
                'items_count' => count($validated['items']),
                'payments_count' => count($validated['payments']),
            ]);

            return DB::transaction(function () use ($validated, $request) {
                $restaurantId = auth()->user()->restaurant_id;
                $cashRegisterSession = CashRegisterSession::findOrFail($validated['cash_register_session_id']);

                // Verificar que la sesión esté abierta
                if ($cashRegisterSession->status !== 'ABIERTA') {
                    return response()->json([
                        'success' => false,
                        'message' => 'La sesión de caja no está abierta'
                    ], 422);
                }

                // Verificar que pertenezca al restaurante
                if ($cashRegisterSession->restaurant_id !== $restaurantId) {
                    abort(403, 'No tienes acceso a esta sesión de caja');
                }

                // Validar productos antes de crear el pedido
                foreach ($validated['items'] as $itemData) {
                    $product = \App\Models\Product::findOrFail($itemData['product_id']);
                    if ($product->restaurant_id !== $restaurantId) {
                        throw new \Exception("El producto '{$product->name}' no pertenece a este restaurante");
                    }
                    if (!$product->is_active) {
                        throw new \Exception("El producto '{$product->name}' no está activo");
                    }
                    // Validar stock antes de crear el pedido
                    if ($product->has_stock) {
                        $currentStock = $product->getCurrentStock($restaurantId);
                        if ($currentStock < $itemData['quantity']) {
                            throw new \Exception("Stock insuficiente para '{$product->name}'. Disponible: {$currentStock}, Solicitado: {$itemData['quantity']}");
                        }
                    }
                }

                // Crear pedido rápido (sin mesa)
                $orderData = [
                    'restaurant_id' => $restaurantId,
                    'user_id' => auth()->id(),
                    'table_id' => null, // Sin mesa
                    'subsector_item_id' => null, // Sin subsector
                    'table_session_id' => null, // Sin sesión de mesa
                    'customer_name' => $validated['customer_name'],
                    'observations' => 'Pedido rápido - Consumo inmediato',
                ];

                $order = $this->orderService->createOrder($orderData);
                Log::info('Pedido creado', ['order_id' => $order->id, 'order_number' => $order->number]);

                // Agregar items al pedido (ya validados arriba)
                foreach ($validated['items'] as $index => $itemData) {
                    try {
                        Log::debug('Agregando item al pedido', [
                            'order_id' => $order->id,
                            'item_index' => $index,
                            'product_id' => $itemData['product_id'],
                            'quantity' => $itemData['quantity'],
                        ]);
                        $this->orderService->addItem($order, $itemData);
                    } catch (\Exception $e) {
                        Log::error('Error al agregar item al pedido', [
                            'order_id' => $order->id,
                            'item_index' => $index,
                            'product_id' => $itemData['product_id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                        // Si falla al agregar un item, cancelar el pedido y lanzar error
                        $order->update(['status' => Order::STATUS_CANCELADO]);
                        throw new \Exception("Error al agregar item al pedido: " . $e->getMessage());
                    }
                }

                // Recalcular total (addItem ya lo hace, pero asegurarnos que esté actualizado)
                $order->refresh();
                $order->calculateTotal();
                $order->refresh();

                // Validar que el pedido tenga items
                $itemsCount = $order->items()->count();
                if ($itemsCount === 0) {
                    Log::error('Pedido sin items', ['order_id' => $order->id]);
                    $order->update(['status' => Order::STATUS_CANCELADO]);
                    throw new \Exception('El pedido no tiene items. No se puede procesar.');
                }

                // Validar que el total del pedido sea mayor a 0
                if ($order->total <= 0) {
                    Log::error('Pedido con total inválido', [
                        'order_id' => $order->id,
                        'total' => $order->total,
                        'items_count' => $itemsCount,
                    ]);
                    $order->update(['status' => Order::STATUS_CANCELADO]);
                    throw new \Exception('El total del pedido debe ser mayor a 0.');
                }

                Log::info('Pedido validado correctamente', [
                    'order_id' => $order->id,
                    'items_count' => $itemsCount,
                    'total' => $order->total,
                ]);

                // Calcular total pagado
                $totalPaid = collect($validated['payments'])->sum(function ($payment) {
                    return (float) $payment['amount'];
                });
                
                // Validar que el total pagado sea válido
                if ($totalPaid <= 0) {
                    $order->update(['status' => Order::STATUS_CANCELADO]);
                    return response()->json([
                        'success' => false,
                        'message' => 'El total pagado debe ser mayor a 0.'
                    ], 422);
                }

                $change = $totalPaid - $order->total;

                // Validar que el pago sea suficiente
                if ($totalPaid < $order->total - 0.01) {
                    // Revertir pedido si el pago es insuficiente
                    $order->update(['status' => Order::STATUS_CANCELADO]);
                    return response()->json([
                        'success' => false,
                        'message' => "El total pagado ($" . number_format($totalPaid, 2) . ") es menor al total a pagar ($" . number_format($order->total, 2) . "). Faltan $" . number_format($order->total - $totalPaid, 2)
                    ], 422);
                }

                // Registrar pagos
                foreach ($validated['payments'] as $index => $paymentData) {
                    try {
                        Payment::create([
                            'restaurant_id' => $restaurantId,
                            'order_id' => $order->id,
                            'cash_register_session_id' => $cashRegisterSession->id,
                            'user_id' => auth()->id(),
                            'payment_method' => $paymentData['payment_method'],
                            'amount' => $paymentData['amount'],
                            'operation_number' => $paymentData['operation_number'] ?? null,
                            'notes' => $paymentData['notes'] ?? 'Pedido rápido - Consumo inmediato',
                        ]);
                        Log::debug('Pago registrado', [
                            'order_id' => $order->id,
                            'payment_index' => $index,
                            'method' => $paymentData['payment_method'],
                            'amount' => $paymentData['amount'],
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error al registrar pago', [
                            'order_id' => $order->id,
                            'payment_index' => $index,
                            'error' => $e->getMessage(),
                        ]);
                        throw new \Exception("Error al registrar pago: " . $e->getMessage());
                    }
                }

                // Cerrar el pedido inmediatamente
                $order->update([
                    'status' => Order::STATUS_CERRADO,
                    'closed_at' => now(),
                ]);

                // Imprimir ticket si se solicita
                $printMessage = '';
                if ($request->boolean('print_ticket')) {
                    try {
                        $printer = $this->printService->getPrinterByType($restaurantId, 'bar');
                        if (!$printer) {
                            $printer = \App\Models\Printer::where('restaurant_id', $restaurantId)
                                ->where('is_active', true)
                                ->first();
                        }
                        if ($printer) {
                            $this->printService->printTicket($order, $printer);
                            $printMessage = ' Ticket impreso.';
                        }
                    } catch (\Exception $printError) {
                        Log::warning('Error al imprimir ticket de pedido rápido: ' . $printError->getMessage());
                    }
                }

                $successMessage = 'Pedido rápido procesado exitosamente.';
                if ($change > 0.01) {
                    $successMessage .= " Cambio: $" . number_format($change, 2) . ".";
                }
                $successMessage .= $printMessage;

                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $successMessage,
                        'order_id' => $order->id,
                        'order_number' => $order->number,
                        'total' => $order->total,
                        'total_paid' => $totalPaid,
                        'change' => $change > 0.01 ? $change : 0,
                        'print_url' => route('orders.print.kitchen', $order),
                    ]);
                }

                return redirect()->route('cash-register.session', $cashRegisterSession)
                    ->with('success', $successMessage);
            });
        } catch (\Exception $e) {
            Log::error('Error al procesar pedido rápido', [
                'user_id' => auth()->id(),
                'restaurant_id' => auth()->user()->restaurant_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar el pedido: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error al procesar el pedido: ' . $e->getMessage())->withInput();
        }
    }
}



<?php

namespace App\Http\Controllers\CashRegister;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\CashMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Category;
use App\Services\CashRegisterService;
use App\Services\OrderService;
use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashRegisterController extends Controller
{
    public function __construct(
        private CashRegisterService $cashRegisterService,
        private OrderService $orderService,
        private PrintService $printService
    ) {
        $this->middleware('role:CAJERO,ADMIN');
    }

    /**
     * Vista principal de caja
     */
    public function index()
    {
        $restaurantId = auth()->user()->restaurant_id;

        // Si es ADMIN, mostrar todas las cajas (activas e inactivas)
        $cashRegistersQuery = CashRegister::where('restaurant_id', $restaurantId);
        
        if (auth()->user()->role !== 'ADMIN') {
            $cashRegistersQuery->where('is_active', true);
        }
        
        $cashRegisters = $cashRegistersQuery->withCount('sessions')
            ->orderBy('name')
            ->get();

        $activeSessions = CashRegisterSession::where('restaurant_id', $restaurantId)
            ->where('status', 'ABIERTA')
            ->with(['cashRegister', 'user'])
            ->get();

        return view('cash-register.index', compact('cashRegisters', 'activeSessions'));
    }

    /**
     * Mostrar formulario de creación de caja
     */
    public function create()
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'Solo los administradores pueden crear cajas');
        }

        return view('cash-register.create');
    }

    /**
     * Crear nueva caja
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'Solo los administradores pueden crear cajas');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['is_active'] = $request->has('is_active');

        CashRegister::create($validated);

        return redirect()->route('cash-register.index')
            ->with('success', 'Caja creada exitosamente');
    }

    /**
     * Mostrar formulario de edición de caja
     */
    public function edit(CashRegister $cashRegister)
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'Solo los administradores pueden editar cajas');
        }

        if ($cashRegister->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a esta caja');
        }

        return view('cash-register.edit', compact('cashRegister'));
    }

    /**
     * Actualizar caja
     */
    public function update(Request $request, CashRegister $cashRegister)
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'Solo los administradores pueden editar cajas');
        }

        if ($cashRegister->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a esta caja');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $cashRegister->update($validated);

        return redirect()->route('cash-register.index')
            ->with('success', 'Caja actualizada exitosamente');
    }

    /**
     * Eliminar caja
     */
    public function destroy(CashRegister $cashRegister)
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'Solo los administradores pueden eliminar cajas');
        }

        if ($cashRegister->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a esta caja');
        }

        // Verificar que no tenga sesiones activas
        if ($cashRegister->sessions()->where('status', 'ABIERTA')->count() > 0) {
            return back()->with('error', 'No se puede eliminar una caja con sesiones abiertas');
        }

        // Verificar que no tenga sesiones históricas (opcional, puedes permitir eliminación)
        if ($cashRegister->sessions()->count() > 0) {
            return back()->with('error', 'No se puede eliminar una caja que tiene sesiones históricas. Desactívala en su lugar.');
        }

        $cashRegister->delete();

        return redirect()->route('cash-register.index')
            ->with('success', 'Caja eliminada exitosamente');
    }

    /**
     * Abrir sesión de caja
     */
    public function openSession(Request $request)
    {
        $validated = $request->validate([
            'cash_register_id' => 'required|exists:cash_registers,id',
            'initial_amount' => 'required|numeric|min:0',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['user_id'] = auth()->id();

        $session = $this->cashRegisterService->openSession($validated);

        return redirect()->route('cash-register.session', $session)
            ->with('success', 'Sesión de caja abierta');
    }

    /**
     * Vista de sesión de caja
     */
    public function session(CashRegisterSession $session)
    {
        $session->load([
            'cashRegister', 
            'user', 
            'payments.order.table', 
            'payments.order.user',
            'payments.user',
            'cashMovements'
        ]);

        // Calcular totales
        $totalPayments = $session->payments()->sum('amount');
        $totalIngresos = $session->cashMovements()->where('type', 'INGRESO')->sum('amount');
        $totalEgresos = $session->cashMovements()->where('type', 'EGRESO')->sum('amount');
        $expectedAmount = $session->initial_amount + $totalPayments + $totalIngresos - $totalEgresos;

        return view('cash-register.session', compact('session', 'totalPayments', 'totalIngresos', 'totalEgresos', 'expectedAmount'));
    }

    /**
     * Cerrar sesión de caja
     */
    public function closeSession(Request $request, CashRegisterSession $session)
    {
        $validated = $request->validate([
            'final_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $this->cashRegisterService->closeSession($session, $validated);

        return redirect()->route('cash-register.index')
            ->with('success', 'Sesión de caja cerrada');
    }

    /**
     * Procesar pago de pedido
     */
    public function processPayment(Request $request, Order $order)
    {
        $validated = $request->validate([
            'cash_register_session_id' => 'required|exists:cash_register_sessions,id',
            'payment_method' => 'required|in:EFECTIVO,DEBITO,CREDITO,TRANSFERENCIA',
            'amount' => 'required|numeric|min:0',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Verificar que el monto sea suficiente
        $remaining = $order->total - $order->payments()->sum('amount');
        if ($validated['amount'] > $remaining) {
            return back()->with('error', 'El monto excede el total pendiente');
        }

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['order_id'] = $order->id;
        $validated['user_id'] = auth()->id();

        $payment = $this->cashRegisterService->recordPayment($validated);

        // Si el pedido está completamente pagado, cerrarlo
        $totalPaid = $order->payments()->sum('amount');
        if ($totalPaid >= $order->total) {
            $order->update(['status' => 'CERRADO', 'closed_at' => now()]);
        }

        return back()->with('success', 'Pago registrado exitosamente');
    }

    /**
     * Eliminar movimiento de caja
     * Solo ADMIN puede eliminar movimientos, y solo si la sesión está abierta
     */
    public function destroyMovement(CashMovement $movement)
    {
        // Solo ADMIN puede eliminar movimientos
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'Solo los administradores pueden eliminar movimientos de caja');
        }

        // Verificar que la sesión esté abierta
        $session = $movement->cashRegisterSession;
        if ($session->status !== 'ABIERTA') {
            return back()->with('error', 'Solo se pueden eliminar movimientos de sesiones abiertas');
        }

        // Verificar que pertenezca al restaurante del usuario
        if ($movement->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a este movimiento');
        }

        try {
            $movement->delete();
            return back()->with('success', 'Movimiento eliminado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el movimiento: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de pedido rápido (consumo inmediato)
     */
    public function quickOrder()
    {
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

        return view('cash-register.quick-order', compact('activeSession', 'products', 'categories'));
    }

    /**
     * Crear y procesar pedido rápido (consumo inmediato)
     */
    public function processQuickOrder(Request $request)
    {
        $validated = $request->validate([
            'cash_register_session_id' => 'required|exists:cash_register_sessions,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.observations' => 'nullable|string',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method' => 'required|in:EFECTIVO,DEBITO,CREDITO,TRANSFERENCIA,QR,MIXTO',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.operation_number' => 'nullable|string|max:255',
            'payments.*.notes' => 'nullable|string|max:500',
            'print_ticket' => 'nullable|boolean',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
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

                // Crear pedido rápido (sin mesa)
                $orderData = [
                    'restaurant_id' => $restaurantId,
                    'user_id' => auth()->id(),
                    'table_id' => null, // Sin mesa
                    'subsector_item_id' => null, // Sin subsector
                    'table_session_id' => null, // Sin sesión de mesa
                    'observations' => 'Pedido rápido - Consumo inmediato',
                    'items' => $validated['items'],
                ];

                $order = $this->orderService->createOrder($orderData);

                // Agregar items al pedido
                foreach ($validated['items'] as $itemData) {
                    $this->orderService->addItem($order, $itemData);
                }

                // Recalcular total
                $order->calculateTotal();
                $order->refresh();

                // Calcular total pagado
                $totalPaid = collect($validated['payments'])->sum('amount');
                $change = $totalPaid - $order->total;

                // Validar que el pago sea suficiente
                if ($totalPaid < $order->total - 0.01) {
                    // Revertir pedido si el pago es insuficiente
                    $order->update(['status' => 'CANCELADO']);
                    return response()->json([
                        'success' => false,
                        'message' => "El total pagado ($${totalPaid}) es menor al total a pagar ($${order->total}). Faltan $" . number_format($order->total - $totalPaid, 2)
                    ], 422);
                }

                // Registrar pagos
                $paymentsCreated = [];
                foreach ($validated['payments'] as $paymentData) {
                    $payment = Payment::create([
                        'restaurant_id' => $restaurantId,
                        'order_id' => $order->id,
                        'cash_register_session_id' => $cashRegisterSession->id,
                        'user_id' => auth()->id(),
                        'payment_method' => $paymentData['payment_method'],
                        'amount' => $paymentData['amount'],
                        'operation_number' => $paymentData['operation_number'] ?? null,
                        'notes' => $paymentData['notes'] ?? 'Pedido rápido - Consumo inmediato',
                    ]);
                    $paymentsCreated[] = $payment;
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
                    ]);
                }

                return redirect()->route('cash-register.session', $cashRegisterSession)
                    ->with('success', $successMessage);
            });
        } catch (\Exception $e) {
            Log::error('Error al procesar pedido rápido: ' . $e->getMessage());
            
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


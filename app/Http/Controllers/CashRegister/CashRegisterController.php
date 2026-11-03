<?php

namespace App\Http\Controllers\CashRegister;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\Order;
use App\Models\User;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CashRegisterController extends Controller
{
    public function __construct(
        private CashRegisterService $cashRegisterService,
    ) {
        $this->middleware('role:CAJERO,ADMIN,GERENTE,ENCARGADO,MOZO');
    }

    /**
     * Vista principal de caja
     */
    public function index()
    {
        $restaurantId = auth()->user()->restaurant_id;

        // Si es ADMIN, mostrar todas las cajas (activas e inactivas)
        $cashRegistersQuery = CashRegister::where('restaurant_id', $restaurantId);

        if (! auth()->user()->isAdminLevel()) {
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
        if (! auth()->user()->isAdminLevel()) {
            abort(403, 'Solo los administradores pueden crear cajas');
        }

        return view('cash-register.create');
    }

    /**
     * Crear nueva caja
     */
    public function store(Request $request)
    {
        if (! auth()->user()->isAdminLevel()) {
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
        if (! auth()->user()->isAdminLevel()) {
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
        if (! auth()->user()->isAdminLevel()) {
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
        if (! auth()->user()->isAdminLevel()) {
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
            'cashMovements',
        ]);

        // Calcular totales
        $totalPayments = $session->payments()->sum('amount');
        $totalIngresos = $session->cashMovements()->where('type', 'INGRESO')->sum('amount');
        $totalEgresos = $session->cashMovements()->where('type', 'EGRESO')->sum('amount');
        // Solo EFECTIVO (+ movimientos manuales) — mismo cálculo que el cierre
        // real, para no mostrar acá un "esperado" que después no coincide al
        // cerrar. Los pagos con tarjeta/transferencia/QR no son efectivo
        // físico; se muestran aparte en $paymentBreakdown.
        $expectedAmount = $this->cashRegisterService->calculateExpectedAmount($session);
        $paymentBreakdown = $this->cashRegisterService->paymentBreakdown($session);

        // Detalle de ventas: pedidos pagados en esta sesión con ítems y productos (con o sin stock)
        $orderIds = $session->payments()->whereNotNull('order_id')->pluck('order_id')->unique()->values();
        $salesDetail = Order::whereIn('id', $orderIds)
            ->with(['table', 'user', 'items.product'])
            ->orderBy('created_at')
            ->get();

        return view('cash-register.session', compact('session', 'totalPayments', 'totalIngresos', 'totalEgresos', 'expectedAmount', 'paymentBreakdown', 'salesDetail'));
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
     * Registrar movimiento de caja (ingreso o egreso/salida)
     * Ej: salida para pago a empleados, gastos, etc.
     */
    public function storeMovement(Request $request, CashRegisterSession $session)
    {
        if ($session->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a esta sesión');
        }
        if ($session->status !== 'ABIERTA') {
            return back()->with('error', 'Solo se pueden registrar movimientos en sesiones abiertas');
        }

        $validated = $request->validate([
            'type' => 'required|in:INGRESO,EGRESO',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:255',
        ]);

        try {
            $this->cashRegisterService->recordMovement([
                'restaurant_id' => auth()->user()->restaurant_id,
                'cash_register_session_id' => $session->id,
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'reference' => $validated['reference'] ?? null,
            ]);
            $tipo = $validated['type'] === 'EGRESO' ? 'Salida' : 'Ingreso';

            return back()->with('success', $tipo.' de caja registrada correctamente');
        } catch (\Exception $e) {
            Log::warning('Error al registrar movimiento de caja: '.$e->getMessage());

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Eliminar movimiento de caja (ingreso/egreso manual).
     * ADMIN puede eliminar siempre (corrección desde reportes).
     * CAJERO puede eliminar solo sus propios movimientos en sesiones ABIERTAS (corrección inmediata).
     */
    public function destroyMovement(CashMovement $movement)
    {
        if ($movement->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a este movimiento');
        }

        $user = auth()->user();
        $isAdmin = $user->isAdminLevel();

        if (! $isAdmin) {
            if (($user->role ?? null) !== User::ROLE_CAJERO) {
                abort(403, 'No tienes permisos para eliminar movimientos de caja');
            }

            $session = $movement->cashRegisterSession;
            if (! $session || $session->restaurant_id !== $user->restaurant_id) {
                abort(403, 'No tienes acceso a esta sesión');
            }
            if ($session->status !== 'ABIERTA') {
                return back()->with('error', 'Solo se pueden eliminar movimientos de sesiones abiertas');
            }
            if ((int) $movement->user_id !== (int) $user->id) {
                abort(403, 'Solo puedes eliminar movimientos registrados por tu usuario');
            }
        }

        try {
            $movement->delete();

            return back()->with('success', 'Movimiento eliminado exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el movimiento: '.$e->getMessage());
        }
    }
}

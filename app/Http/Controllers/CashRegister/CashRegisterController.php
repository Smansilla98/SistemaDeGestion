<?php

namespace App\Http\Controllers\CashRegister;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\Order;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    public function __construct(
        private CashRegisterService $cashRegisterService
    ) {
        $this->middleware('role:CAJERO,ADMIN');
    }

    /**
     * Vista principal de caja
     */
    public function index()
    {
        $restaurantId = auth()->user()->restaurant_id;

        $cashRegisters = CashRegister::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->get();

        $activeSessions = CashRegisterSession::where('restaurant_id', $restaurantId)
            ->where('status', 'ABIERTA')
            ->with(['cashRegister', 'user'])
            ->get();

        return view('cash-register.index', compact('cashRegisters', 'activeSessions'));
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
}


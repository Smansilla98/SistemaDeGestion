<?php

namespace App\Services;

use App\Models\CashRegisterSession;
use App\Models\Payment;
use App\Models\CashMovement;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    /**
     * Abrir sesión de caja
     */
    public function openSession(array $data): CashRegisterSession
    {
        // Verificar si ya hay una sesión abierta
        $existingSession = CashRegisterSession::where('cash_register_id', $data['cash_register_id'])
            ->where('status', 'ABIERTA')
            ->first();

        if ($existingSession) {
            throw new \Exception('Ya existe una sesión de caja abierta');
        }

        return CashRegisterSession::create([
            'restaurant_id' => $data['restaurant_id'],
            'cash_register_id' => $data['cash_register_id'],
            'user_id' => $data['user_id'],
            'initial_amount' => $data['initial_amount'],
            'status' => 'ABIERTA',
            'opened_at' => now(),
        ]);
    }

    /**
     * Cerrar sesión de caja
     */
    public function closeSession(CashRegisterSession $session, array $data): CashRegisterSession
    {
        return DB::transaction(function () use ($session, $data) {
            if ($session->status === 'CERRADA') {
                throw new \Exception('La sesión ya está cerrada');
            }

            // Calcular monto esperado
            $expectedAmount = $this->calculateExpectedAmount($session);

            // Calcular diferencia
            $difference = $data['final_amount'] - $expectedAmount;

            $session->update([
                'final_amount' => $data['final_amount'],
                'expected_amount' => $expectedAmount,
                'difference' => $difference,
                'status' => 'CERRADA',
                'closed_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            return $session->fresh();
        });
    }

    /**
     * Calcular monto esperado según ventas
     */
    private function calculateExpectedAmount(CashRegisterSession $session): float
    {
        // Sumar todos los pagos en efectivo de la sesión
        $payments = Payment::where('cash_register_session_id', $session->id)
            ->where('payment_method', 'EFECTIVO')
            ->sum('amount');

        // Sumar ingresos manuales
        $ingresos = CashMovement::where('cash_register_session_id', $session->id)
            ->where('type', 'INGRESO')
            ->sum('amount');

        // Restar egresos manuales
        $egresos = CashMovement::where('cash_register_session_id', $session->id)
            ->where('type', 'EGRESO')
            ->sum('amount');

        return $session->initial_amount + $payments + $ingresos - $egresos;
    }

    /**
     * Registrar movimiento de caja
     */
    public function recordMovement(array $data): CashMovement
    {
        $session = CashRegisterSession::findOrFail($data['cash_register_session_id']);

        if ($session->status !== 'ABIERTA') {
            throw new \Exception('Solo se pueden registrar movimientos en sesiones abiertas');
        }

        return CashMovement::create([
            'restaurant_id' => $data['restaurant_id'],
            'cash_register_session_id' => $data['cash_register_session_id'],
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'reference' => $data['reference'] ?? null,
        ]);
    }

    /**
     * Registrar pago
     */
    public function recordPayment(array $data): Payment
    {
        $session = CashRegisterSession::findOrFail($data['cash_register_session_id']);

        if ($session->status !== 'ABIERTA') {
            throw new \Exception('Solo se pueden registrar pagos en sesiones abiertas');
        }

        return Payment::create([
            'restaurant_id' => $data['restaurant_id'],
            'order_id' => $data['order_id'],
            'cash_register_session_id' => $data['cash_register_session_id'],
            'user_id' => $data['user_id'],
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}


<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CashRegisterSession;
use Illuminate\Http\Request;

class MobileCajaController extends Controller
{
    public function resumen(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;
        $today = now();
        $start = $today->copy()->startOfDay();
        $end = $today->copy()->endOfDay();

        $sessions = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('opened_at', [$start, $end])
                    ->orWhereBetween('closed_at', [$start, $end]);
            })
            ->with(['cashRegister', 'user', 'payments', 'cashMovements'])
            ->orderByDesc('opened_at')
            ->get()
            ->map(function (CashRegisterSession $session) {
                $totalPayments = $session->payments->sum('amount');
                $totalIngresos = $session->cashMovements->where('type', 'INGRESO')->sum('amount');
                $totalEgresos = $session->cashMovements->where('type', 'EGRESO')->sum('amount');
                $expectedAmount = ($session->initial_amount ?? 0) + $totalPayments + $totalIngresos - $totalEgresos;

                $session->setAttribute('total_payments', $totalPayments);
                $session->setAttribute('total_ingresos', $totalIngresos);
                $session->setAttribute('total_egresos', $totalEgresos);
                $session->setAttribute('expected_amount', $expectedAmount);

                return $session;
            });

        $totals = [
            'payments' => $sessions->sum('total_payments'),
            'ingresos' => $sessions->sum('total_ingresos'),
            'egresos' => $sessions->sum('total_egresos'),
            'neto' => $sessions->sum(fn ($s) => ($s->total_payments + $s->total_ingresos - $s->total_egresos)),
        ];

        return view('mobile.caja.resumen', [
            'sessions' => $sessions,
            'totals' => $totals,
            'dateLabel' => $today->format('d/m/Y'),
        ]);
    }
}


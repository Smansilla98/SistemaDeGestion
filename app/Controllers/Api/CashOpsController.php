<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Services\CashRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CashOpsController extends Controller
{
    public function registers(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $rows = CashRegister::query()
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        return ApiResponse::success($rows->toArray());
    }

    public function currentSession(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $session = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', CashRegisterSession::STATUS_ABIERTA)
            ->with(['cashRegister:id,name', 'user:id,name'])
            ->orderByDesc('opened_at')
            ->first();

        return ApiResponse::success($session?->toArray());
    }

    public function open(Request $request, int $registerId, CashRegisterService $cash): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $register = CashRegister::query()
            ->where('restaurant_id', $restaurantId)
            ->find($registerId);

        if ($register === null) {
            return ApiResponse::error('Caja no encontrada', 404, 'NOT_FOUND');
        }

        $validated = $request->validate([
            'initial_amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $session = $cash->openSession([
                'restaurant_id' => $restaurantId,
                'cash_register_id' => $register->id,
                'user_id' => (int) $request->user()->id,
                'initial_amount' => $validated['initial_amount'],
            ]);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'OPEN_CASH_ERROR');
        }

        return ApiResponse::success($session->toArray(), 201, 'Caja abierta');
    }

    public function summary(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $session = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', CashRegisterSession::STATUS_ABIERTA)
            ->orderByDesc('opened_at')
            ->first();

        if ($session === null) {
            return ApiResponse::success([
                'session' => null,
                'sales_total' => 0,
                'payments_count' => 0,
                'expected_amount' => 0,
            ]);
        }

        $payments = $session->payments();
        $sales = (float) (clone $payments)->sum('amount');
        $count = (clone $payments)->count();
        $ingresos = (float) $session->cashMovements()->where('type', 'INGRESO')->sum('amount');
        $egresos = (float) $session->cashMovements()->where('type', 'EGRESO')->sum('amount');
        $expected = (float) $session->initial_amount + $sales + $ingresos - $egresos;

        return ApiResponse::success([
            'session' => $session->load('cashRegister:id,name')->toArray(),
            'sales_total' => $sales,
            'payments_count' => $count,
            'expected_amount' => $expected,
        ]);
    }

    public function close(Request $request, CashRegisterService $cash): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $session = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', CashRegisterSession::STATUS_ABIERTA)
            ->orderByDesc('opened_at')
            ->first();

        if ($session === null) {
            return ApiResponse::error('No hay sesión de caja abierta', 422, 'NO_OPEN_SESSION');
        }

        $validated = $request->validate([
            'final_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $closed = $cash->closeSession($session, $validated);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'CLOSE_CASH_ERROR');
        }

        return ApiResponse::success($closed->toArray(), 200, 'Caja cerrada');
    }

    public function sessions(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $rows = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['cashRegister:id,name', 'user:id,name'])
            ->orderByDesc('opened_at')
            ->limit(40)
            ->get()
            ->map(fn (CashRegisterSession $s) => [
                'id' => $s->id,
                'status' => $s->status,
                'register' => $s->cashRegister?->name,
                'user' => $s->user?->name,
                'initial_amount' => (float) $s->initial_amount,
                'final_amount' => $s->final_amount !== null ? (float) $s->final_amount : null,
                'opened_at' => optional($s->opened_at)->toIso8601String(),
                'closed_at' => optional($s->closed_at)->toIso8601String(),
            ]);

        return ApiResponse::success($rows->values()->all());
    }

    public function sessionDetail(Request $request, int $sessionId): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $session = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['cashRegister:id,name', 'user:id,name', 'payments.order.table', 'cashMovements'])
            ->find($sessionId);

        if ($session === null) {
            return ApiResponse::error('Sesión no encontrada', 404, 'NOT_FOUND');
        }

        $sales = (float) $session->payments()->sum('amount');
        $ingresos = (float) $session->cashMovements()->where('type', 'INGRESO')->sum('amount');
        $egresos = (float) $session->cashMovements()->where('type', 'EGRESO')->sum('amount');
        $expected = (float) $session->initial_amount + $sales + $ingresos - $egresos;

        return ApiResponse::success([
            'session' => $session->toArray(),
            'sales_total' => $sales,
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'expected_amount' => $expected,
            'payments' => $session->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'payment_method' => $p->payment_method,
                'order_number' => $p->order?->number,
                'table' => $p->order?->table?->number,
                'created_at' => optional($p->created_at)->toIso8601String(),
            ])->values()->all(),
            'movements' => $session->cashMovements->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'amount' => (float) $m->amount,
                'description' => $m->description,
                'created_at' => optional($m->created_at)->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    public function storeMovement(Request $request, CashRegisterService $cash): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $session = CashRegisterSession::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', CashRegisterSession::STATUS_ABIERTA)
            ->orderByDesc('opened_at')
            ->first();

        if ($session === null) {
            return ApiResponse::error('No hay sesión de caja abierta', 422, 'NO_OPEN_SESSION');
        }

        $validated = $request->validate([
            'type' => 'required|in:INGRESO,EGRESO',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
        ]);

        try {
            $movement = $cash->recordMovement([
                'restaurant_id' => $restaurantId,
                'cash_register_session_id' => $session->id,
                'user_id' => (int) $request->user()->id,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'reference' => $validated['reference'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'CASH_MOVEMENT_ERROR');
        }

        return ApiResponse::success($movement->toArray(), 201, 'Movimiento registrado');
    }

    private function requireRestaurantId(Request $request): int|JsonResponse
    {
        $rid = $request->user()?->restaurant_id;
        if (! $rid) {
            return ApiResponse::error('Usuario sin restaurante asignado', 403, 'NO_RESTAURANT');
        }

        return (int) $rid;
    }
}

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
            ]);
        }

        $payments = $session->payments();
        $sales = (clone $payments)->sum('amount');
        $count = (clone $payments)->count();

        return ApiResponse::success([
            'session' => $session->load('cashRegister:id,name')->toArray(),
            'sales_total' => (float) $sales,
            'payments_count' => $count,
        ]);
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

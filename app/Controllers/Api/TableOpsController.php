<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Models\Table;
use App\Services\OrderService;
use App\Services\TableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class TableOpsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Table::class);

        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $tables = Table::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['sector:id,name', 'currentSession:id,waiter_id,started_at'])
            ->orderBy('number')
            ->get()
            ->map(fn (Table $t) => [
                'id' => $t->id,
                'number' => $t->number,
                'status' => $t->status,
                'capacity' => $t->capacity,
                'sector' => $t->sector?->name,
                'sector_id' => $t->sector_id,
                'current_session_id' => $t->current_session_id,
                'current_order_id' => $t->current_order_id,
            ]);

        return ApiResponse::success($tables->values()->all());
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $table = Table::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['sector', 'orders' => fn ($q) => $q->whereNotIn('status', ['CERRADO', 'CANCELADO'])->latest()])
            ->find($id);

        if ($table === null) {
            return ApiResponse::error('Mesa no encontrada', 404, 'NOT_FOUND');
        }

        $this->authorize('view', $table);

        return ApiResponse::success($table->toArray());
    }

    public function occupy(Request $request, int $id, OrderService $orders): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $table = Table::query()->where('restaurant_id', $restaurantId)->find($id);
        if ($table === null) {
            return ApiResponse::error('Mesa no encontrada', 404, 'NOT_FOUND');
        }

        Gate::authorize('update', $table);

        try {
            $orders->ensureTableReadyForOrder($table, (int) $request->user()->id);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'OCCUPY_ERROR');
        }

        return ApiResponse::success($table->fresh()->toArray(), 200, 'Mesa ocupada');
    }

    public function pay(Request $request, int $id, TableService $tables): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $table = Table::query()->where('restaurant_id', $restaurantId)->find($id);
        if ($table === null) {
            return ApiResponse::error('Mesa no encontrada', 404, 'NOT_FOUND');
        }

        Gate::authorize('update', $table);

        $validated = $request->validate([
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method' => ['required', 'string'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'discount_type_id' => ['nullable', 'integer'],
        ]);

        try {
            $result = $tables->processTablePayment($table, $validated, (int) $request->user()->id);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'PAY_ERROR');
        }

        if (! ($result['success'] ?? false)) {
            return ApiResponse::error($result['message'] ?? 'No se pudo cobrar', 422, 'PAY_ERROR');
        }

        unset($result['redirect'], $result['flash']);

        return ApiResponse::success($result, 200, 'Mesa cobrada');
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

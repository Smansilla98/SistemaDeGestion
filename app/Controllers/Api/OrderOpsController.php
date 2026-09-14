<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class OrderOpsController extends Controller
{
    public function sendToKitchen(Request $request, int $id, OrderService $orders): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $order = Order::query()->where('restaurant_id', $restaurantId)->find($id);
        if ($order === null) {
            return ApiResponse::error('Pedido no encontrado', 404, 'NOT_FOUND');
        }

        Gate::authorize('update', $order);

        try {
            $fresh = $orders->sendToKitchen($order);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'SEND_KITCHEN_ERROR');
        }

        return ApiResponse::success($fresh->load(['items.product', 'table'])->toArray(), 200, 'Enviado a cocina');
    }

    public function transition(Request $request, int $id): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $order = Order::query()->where('restaurant_id', $restaurantId)->find($id);
        if ($order === null) {
            return ApiResponse::error('Pedido no encontrado', 404, 'NOT_FOUND');
        }

        Gate::authorize('update', $order);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_column(OrderStatus::cases(), 'value'))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $target = OrderStatus::from($validated['status']);
            $order->transitionTo($target, $request->user(), $validated['note'] ?? null);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'TRANSITION_ERROR');
        }

        return ApiResponse::success($order->fresh()->toArray());
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

<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Domain\Money\Money;
use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\DiscountType;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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

        return ApiResponse::success($order->fresh(['items.product', 'table'])->toArray());
    }

    public function addItem(Request $request, int $id, OrderService $orders): JsonResponse
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

        if (in_array($order->status, [Order::STATUS_CERRADO, Order::STATUS_CANCELADO], true)) {
            return ApiResponse::error('No se pueden agregar ítems a un pedido cerrado o cancelado', 422, 'ORDER_LOCKED');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'observations' => 'nullable|string|max:500',
        ]);

        try {
            $item = $orders->addItem($order, $validated);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'ADD_ITEM_ERROR');
        }

        return ApiResponse::success([
            'item' => $item->load('product')->toArray(),
            'order' => $order->fresh(['items.product', 'table'])->toArray(),
        ], 201, 'Ítem agregado');
    }

    public function removeItems(Request $request, int $id, OrderService $orders): JsonResponse
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
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer',
        ]);

        try {
            $orders->removeOrderItems($order, $validated['item_ids']);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'REMOVE_ITEM_ERROR');
        }

        return ApiResponse::success($order->fresh(['items.product', 'table'])->toArray(), 200, 'Ítems eliminados');
    }

    public function applyDiscount(Request $request, int $id): JsonResponse
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

        $user = $request->user();
        $role = $user->role ?? '';
        $allowed = method_exists($user, 'canManageOrdersLikeAdmin') && $user->canManageOrdersLikeAdmin();
        if (! $allowed && ! in_array($role, ['ADMIN', 'SUPERADMIN', 'GERENTE', 'ENCARGADO'], true)) {
            return ApiResponse::error('No tenés permiso para aplicar descuentos', 403, 'FORBIDDEN');
        }

        if (in_array($order->status, [Order::STATUS_CERRADO, Order::STATUS_CANCELADO], true)) {
            return ApiResponse::error('No se puede descontar un pedido cerrado o anulado', 422, 'ORDER_LOCKED');
        }

        $validated = $request->validate([
            'discount_type_id' => 'nullable|exists:discount_types,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldDiscount = (float) $order->discount;
        $newDiscount = 0.0;

        if (! empty($validated['discount_type_id'])) {
            $discountType = DiscountType::find($validated['discount_type_id']);
            if (! $discountType || (int) $discountType->restaurant_id !== $restaurantId) {
                return ApiResponse::error('Tipo de descuento inválido', 422, 'INVALID_DISCOUNT');
            }
            $newDiscount = (float) $discountType->calculateDiscount($order->subtotal);
        }

        $payload = [
            'discount' => $newDiscount,
            'total' => max(0, (float) $order->subtotal - $newDiscount),
        ];
        if (Schema::hasColumn('orders', 'discount_cents')) {
            $payload['discount_cents'] = Money::fromDecimal($newDiscount)->cents;
            $payload['subtotal_cents'] = Money::fromDecimal($order->subtotal)->cents;
            $payload['total_cents'] = max(0, $payload['subtotal_cents'] - $payload['discount_cents']);
        }

        $order->update($payload);

        AuditLog::record($order, 'discount.applied', [
            'discount' => $oldDiscount,
        ], [
            'discount' => $newDiscount,
            'discount_type_id' => $validated['discount_type_id'] ?? null,
        ], $validated['reason'] ?? 'Descuento API mobile');

        return ApiResponse::success($order->fresh(['items.product', 'table'])->toArray(), 200, 'Descuento aplicado');
    }

    public function cancel(Request $request, int $id, OrderService $orders): JsonResponse
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
            'reason' => 'nullable|string|max:255',
            'hard_delete' => 'sometimes|boolean',
        ]);

        try {
            if ($validated['hard_delete'] ?? false) {
                $orders->deleteOrder($order, $validated['reason'] ?? null);

                return ApiResponse::success(null, 200, 'Pedido eliminado');
            }
            $order->transitionTo(OrderStatus::CANCELADO, $request->user(), $validated['reason'] ?? 'Anulado desde mobile');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'CANCEL_ERROR');
        }

        return ApiResponse::success($order->fresh()->toArray(), 200, 'Pedido anulado');
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

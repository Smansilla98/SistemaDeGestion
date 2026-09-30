<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KitchenOpsController extends Controller
{
    public function board(Request $request): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $query = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', OrderStatus::kitchenBoard())
            ->with(['table', 'table.sector', 'user', 'items.product', 'items.modifiers']);

        if ($request->filled('sector')) {
            $query->whereHas('table', fn ($q) => $q->where('sector_id', $request->integer('sector')));
        }

        $orders = $query->orderBy('created_at')->get();

        $payload = $orders->map(fn (Order $order) => [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status,
            'sent_at' => optional($order->sent_at ?? $order->created_at)->toIso8601String(),
            'table' => $order->table?->number,
            'sector' => $order->table?->sector?->name,
            'waiter' => $order->user?->name,
            'observations' => $order->observations,
            'items_count' => $order->items->count(),
            'items' => $order->items->map(fn (OrderItem $i) => [
                'id' => $i->id,
                'name' => $i->product?->name ?? $i->product_name_snapshot,
                'quantity' => $i->quantity,
                'status' => $i->status,
                'observations' => $i->observations,
                'modifiers' => $i->modifiers->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'price_modifier' => $m->price_modifier,
                ])->values()->all(),
            ])->values()->all(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ]);

        return ApiResponse::success([
            'counts' => [
                'ENVIADO' => $orders->where('status', OrderStatus::ENVIADO->value)->count(),
                'EN_PREPARACION' => $orders->where('status', OrderStatus::EN_PREPARACION->value)->count(),
                'LISTO' => $orders->where('status', OrderStatus::LISTO->value)->count(),
            ],
            'orders' => $payload->values()->all(),
        ]);
    }

    public function updateItemStatus(Request $request, int $itemId, OrderService $orders): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $item = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->find($itemId);

        if ($item === null) {
            return ApiResponse::error('Ítem no encontrado', 404, 'NOT_FOUND');
        }

        $validated = $request->validate([
            'status' => 'required|in:EN_PREPARACION,LISTO,ENTREGADO',
        ]);

        $orders->updateItemStatus($item, $validated['status']);

        $order = $item->order()->first();
        if ($order) {
            $allDone = $order->items()->whereNotIn('status', ['LISTO', 'ENTREGADO'])->count() === 0;
            if ($allDone && in_array($order->status, [OrderStatus::EN_PREPARACION->value, OrderStatus::ENVIADO->value], true)) {
                $order->transitionTo(OrderStatus::LISTO, $request->user(), 'Ítems listos');
            } elseif ($order->status === OrderStatus::ENVIADO->value && $validated['status'] === 'EN_PREPARACION') {
                $order->transitionTo(OrderStatus::EN_PREPARACION, $request->user(), 'Cocina tomó el pedido');
            }
        }

        return ApiResponse::success([
            'item' => $item->fresh(['product'])->toArray(),
            'order' => $order?->fresh()?->toArray(),
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

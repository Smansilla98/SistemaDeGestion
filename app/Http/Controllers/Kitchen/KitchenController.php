<?php

namespace App\Http\Controllers\Kitchen;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\OrderDispatchedNotification;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private NotificationService $notificationService
    ) {
        $this->middleware('role:COCINA,ADMIN');
    }

    /**
     * KDS: columnas ENVIADO / EN_PREPARACION / LISTO (alineadas con la vista).
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', OrderStatus::kitchenBoard())
            ->with(['table', 'table.sector', 'user', 'items.product', 'items.modifiers']);

        if ($request->has('sector')) {
            $query->whereHas('table', function ($q) use ($request) {
                $q->where('sector_id', $request->sector);
            });
        }

        $orders = $query->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('status');

        return view('kitchen.index', compact('orders'));
    }

    /**
     * Tablero KDS en JSON para refresh parcial (sin location.reload).
     */
    public function boardJson(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', OrderStatus::kitchenBoard())
            ->with(['table', 'table.sector', 'user', 'items.product']);

        if ($request->has('sector')) {
            $query->whereHas('table', function ($q) use ($request) {
                $q->where('sector_id', $request->sector);
            });
        }

        $orders = $query->orderBy('created_at', 'asc')->get();

        $payload = $orders->map(function (Order $order) {
            return [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status,
                'sent_at' => optional($order->sent_at ?? $order->created_at)->toIso8601String(),
                'table' => $order->table?->number,
                'sector' => $order->table?->sector?->name,
                'waiter' => $order->user?->name,
                'items_count' => $order->items->count(),
                'items_ready' => $order->items->where('status', 'LISTO')->count()
                    + $order->items->where('status', 'ENTREGADO')->count(),
                'updated_at' => $order->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'counts' => [
                'ENVIADO' => $orders->where('status', 'ENVIADO')->count(),
                'EN_PREPARACION' => $orders->where('status', 'EN_PREPARACION')->count(),
                'LISTO' => $orders->where('status', 'LISTO')->count(),
            ],
            'orders' => $payload,
            'signature' => md5($payload->pluck('id')->sort()->implode(',').'|'.$payload->pluck('status')->implode(',')),
        ]);
    }

    public function updateItemStatus(Request $request, OrderItem $item)
    {
        $validated = $request->validate([
            'status' => 'required|in:EN_PREPARACION,LISTO,ENTREGADO',
        ]);

        $this->orderService->updateItemStatus($item, $validated['status']);

        $order = $item->order;
        $allItemsReady = $order->items()
            ->where('status', '!=', 'ENTREGADO')
            ->count() === 0;

        if ($allItemsReady && in_array($order->status, [OrderStatus::EN_PREPARACION->value, OrderStatus::ENVIADO->value], true)) {
            $order->transitionTo(OrderStatus::LISTO, auth()->user(), 'Ítems listos');
        } elseif ($order->status === OrderStatus::ENVIADO->value && $validated['status'] === 'EN_PREPARACION') {
            $order->transitionTo(OrderStatus::EN_PREPARACION, auth()->user(), 'Cocina tomó el pedido');
        }

        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'item' => $item->fresh(['product']),
                'order' => $order->fresh(),
            ]);
        }

        return back()->with('success', 'Estado actualizado');
    }

    public function markOrderReady(Order $order)
    {
        $order->transitionTo(OrderStatus::LISTO, auth()->user(), 'Marcado listo desde KDS');

        $this->notificationService->notifyOrderReady($order);
        User::where('restaurant_id', $order->restaurant_id)
            ->where('is_active', true)
            ->whereIn('role', ['MOZO', 'ENCARGADO', 'ADMIN', 'CAJERO'])
            ->get()
            ->each(fn ($u) => $u->notify(new OrderDispatchedNotification($order, 'LISTO')));

        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pedido marcado como listo. El mozo será notificado.',
                'order' => $order->fresh(['table', 'user']),
            ]);
        }

        return back()->with('success', 'Pedido marcado como listo. El mozo será notificado.');
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:ENVIADO,EN_PREPARACION,LISTO,ENTREGADO',
        ]);

        $target = OrderStatus::from($validated['status']);
        $order->transitionTo($target, auth()->user(), 'Cambio desde KDS');

        if (in_array($validated['status'], ['LISTO', 'ENTREGADO'], true)) {
            $this->notificationService->notifyOrderReady($order);
            User::where('restaurant_id', $order->restaurant_id)
                ->where('is_active', true)
                ->whereIn('role', ['MOZO', 'ENCARGADO', 'ADMIN', 'CAJERO'])
                ->get()
                ->each(fn ($u) => $u->notify(new OrderDispatchedNotification($order, $validated['status'])));
        }

        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Estado del pedido actualizado',
                'order' => $order->fresh(['table', 'user']),
            ]);
        }

        return back()->with('success', 'Estado del pedido actualizado');
    }

    /**
     * Pedidos LISTO del local (no solo mesas del mozo logueado).
     */
    public function getReadyOrdersNotifications(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;
        $user = auth()->user();

        $query = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', [OrderStatus::LISTO->value, OrderStatus::ENTREGADO->value])
            ->where('updated_at', '>=', now()->subMinutes(5))
            ->with(['table', 'table.sector']);

        // Mozos: priorizar sus mesas, pero no ocultar el resto del salón
        if (($user->role ?? null) === 'MOZO') {
            $query->where(function ($q) use ($user) {
                $q->whereHas('table.currentSession', function ($sq) use ($user) {
                    $sq->where('waiter_id', $user->id)->where('status', 'ABIERTA');
                })->orWhere('user_id', $user->id)
                    ->orWhereNull('table_id');
            });
        }

        $readyOrders = $query->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'number' => $order->number,
                    'table_number' => $order->table->number ?? ($order->customer_name ?: 'Barra'),
                    'table_id' => $order->table_id,
                    'table_sector' => $order->table->sector->name ?? 'N/A',
                    'updated_at' => $order->updated_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'orders' => $readyOrders,
            'count' => $readyOrders->count(),
        ]);
    }
}

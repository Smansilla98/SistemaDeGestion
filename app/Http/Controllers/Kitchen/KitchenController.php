<?php

namespace App\Http\Controllers\Kitchen;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransition;
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
        // El aviso de pedidos listos lo consultan los mozos desde Mesas.
        $this->middleware('role:COCINA,ADMIN')->except('getReadyOrdersNotifications');
    }

    /**
     * Pantalla de cocina: solo lectura. La vista se dibuja y se refresca sola desde boardJson.
     */
    public function index(Request $request)
    {
        return view('kitchen.index', [
            'boardUrl' => route('kitchen.board-json', $request->only('sector')),
            'refreshSeconds' => 5,
        ]);
    }

    /**
     * Tablero completo en JSON (pedidos + ítems) para el refresco continuo de la pantalla.
     */
    public function boardJson(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', OrderStatus::kitchenBoard())
            ->with(['table', 'table.sector', 'subsectorItem', 'user', 'items.product', 'items.modifiers']);

        if ($request->filled('sector')) {
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
                'place' => $order->table
                    ? $order->table->displayName()
                    : ($order->subsectorItem?->name ?? ($order->customer_name ?: 'Barra')),
                'sector' => $order->table?->sector?->name,
                'waiter' => $order->user?->name,
                'observations' => $order->observations,
                'items_count' => $order->items->count(),
                'items_ready' => $order->items->where('status', 'LISTO')->count()
                    + $order->items->where('status', 'ENTREGADO')->count(),
                'items' => $order->items->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'name' => $item->product?->name ?? $item->product_name_snapshot,
                    'quantity' => $item->quantity,
                    'status' => $item->status,
                    'observations' => $item->observations,
                    'modifiers' => $item->modifiers->pluck('name')->values()->all(),
                ])->values()->all(),
                'updated_at' => $order->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'server_time' => now()->toIso8601String(),
            'counts' => [
                'ENVIADO' => $orders->where('status', 'ENVIADO')->count(),
                'EN_PREPARACION' => $orders->where('status', 'EN_PREPARACION')->count(),
                'LISTO' => $orders->where('status', 'LISTO')->count(),
            ],
            'orders' => $payload,
            'signature' => md5($payload->pluck('id')->sort()->implode(',').'|'.$payload->pluck('status')->implode(',')),
        ]);
    }

    /**
     * Cocina tilda un ítem como hecho (LISTO) o lo vuelve a preparación.
     * El pedido acompaña: arranca la preparación con el primer ítem y pasa a
     * LISTO (avisando al mozo) cuando no queda ninguno pendiente.
     */
    public function updateItemStatus(Request $request, OrderItem $item)
    {
        $validated = $request->validate([
            'status' => 'required|in:EN_PREPARACION,LISTO,ENTREGADO',
        ]);

        // OrderItem no tiene alcance por local: el pedido sí, así que se valida por él.
        $order = $item->order;
        abort_if($order === null, 404);

        $this->orderService->updateItemStatus($item, $validated['status']);
        $order->refresh();

        $pending = $order->items()->whereNotIn('status', ['LISTO', 'ENTREGADO'])->count();
        $current = $order->statusEnum();
        $actor = auth()->user();

        try {
            if ($pending === 0 && in_array($current, [OrderStatus::ENVIADO, OrderStatus::EN_PREPARACION], true)) {
                $order->transitionTo(OrderStatus::LISTO, $actor, 'Todos los ítems listos (KDS)');
                $this->notifyReady($order, 'LISTO');
            } elseif ($pending > 0 && $current === OrderStatus::ENVIADO) {
                $order->transitionTo(OrderStatus::EN_PREPARACION, $actor, 'Cocina empezó el pedido');
            } elseif ($pending > 0 && $current === OrderStatus::LISTO) {
                $order->transitionTo(OrderStatus::EN_PREPARACION, $actor, 'Cocina reabrió un ítem');
            }
        } catch (InvalidOrderTransition $e) {
            // El ítem quedó marcado; el pedido sigue en el estado que tenía.
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
        $this->notifyReady($order, 'LISTO');

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
        try {
            $order->transitionTo($target, auth()->user(), 'Cambio desde KDS');
        } catch (InvalidOrderTransition $e) {
            $message = 'Este pedido ya cambió de estado. La pantalla se actualiza sola.';
            if (request()->wantsJson() || request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        // Al volver a preparación, los ítems se destildan para que la cocina los vea pendientes.
        if ($target === OrderStatus::EN_PREPARACION || $target === OrderStatus::ENVIADO) {
            $order->items()->where('status', 'LISTO')->update(['status' => 'EN_PREPARACION']);
        }

        if (in_array($validated['status'], ['LISTO', 'ENTREGADO'], true)) {
            $this->notifyReady($order, $validated['status']);
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

    private function notifyReady(Order $order, string $status): void
    {
        $this->notificationService->notifyOrderReady($order);
        User::where('restaurant_id', $order->restaurant_id)
            ->where('is_active', true)
            ->whereIn('role', ['MOZO', 'ENCARGADO', 'ADMIN', 'CAJERO'])
            ->get()
            ->each(fn ($u) => $u->notify(new OrderDispatchedNotification($order, $status)));
    }
}

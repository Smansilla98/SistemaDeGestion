<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use App\Services\NotificationService;
use App\Events\KitchenOrderReady;
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
     * Vista principal de cocina (KDS - Kitchen Display System)
     * MÓDULO 3: Tablero estilo KDS con tarjetas grandes por pedido
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        // MÓDULO 3: Obtener pedidos en estados relevantes para cocina
        $query = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['ABIERTO', 'EN_PREPARACION', 'ENTREGADO'])
            ->with(['table', 'table.sector', 'user', 'items.product', 'items.modifiers']);

        // Filtrar por sector si se especifica
        if ($request->has('sector')) {
            $query->whereHas('table', function($q) use ($request) {
                $q->where('sector_id', $request->sector);
            });
        }

        $orders = $query->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('status');

        return view('kitchen.index', compact('orders'));
    }

    /**
     * Actualizar estado de item
     */
    public function updateItemStatus(Request $request, OrderItem $item)
    {
        $validated = $request->validate([
            'status' => 'required|in:PENDIENTE,EN_PREPARACION,LISTO,ENTREGADO',
        ]);

        $this->orderService->updateItemStatus($item, $validated['status']);

        // Actualizar estado del pedido si es necesario
        $order = $item->order;
        $allItemsReady = $order->items()
            ->where('status', '!=', 'ENTREGADO')
            ->count() === 0;

        if ($allItemsReady && $order->status === 'EN_PREPARACION') {
            $order->update(['status' => 'LISTO']);
        } elseif ($order->status === 'ENVIADO' && $validated['status'] === 'EN_PREPARACION') {
            $order->update(['status' => 'EN_PREPARACION']);
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

    /**
     * Marcar pedido como listo
     * MÓDULO 3: Notificar al mozo cuando el pedido está listo
     */
    public function markOrderReady(Order $order)
    {
        // Cambiar estado a ENTREGADO (en el nuevo flujo, LISTO no existe)
        $order->update(['status' => 'ENTREGADO']);

        // Notificar al mozo que el pedido está listo
        $this->notificationService->notifyOrderReady($order);

        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pedido marcado como listo. El mozo será notificado.',
                'order' => $order->fresh(['table', 'user']),
            ]);
        }

        return back()->with('success', 'Pedido marcado como listo. El mozo será notificado.');
    }
    
    /**
     * Actualizar estado del pedido desde KDS
     * MÓDULO 3: Permite cambiar estado desde cocina
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:EN_PREPARACION,ENTREGADO'
        ]);

        $order->update(['status' => $validated['status']]);

        if ($validated['status'] === 'ENTREGADO') {
            // Notificar al mozo que el pedido está listo
            $this->notificationService->notifyOrderReady($order);
            $order->load(['table', 'user']);
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
     * API: Obtener notificaciones de pedidos listos para el mozo
     * MÓDULO 3: Endpoint para polling de notificaciones
     */
    public function getReadyOrdersNotifications(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;
        $userId = auth()->id();
        
        // Obtener pedidos que cambiaron a ENTREGADO en los últimos 5 minutos
        // y que pertenecen a mesas atendidas por este mozo
        $readyOrders = Order::where('restaurant_id', $restaurantId)
            ->where('status', 'ENTREGADO')
            ->where('updated_at', '>=', now()->subMinutes(5))
            ->whereHas('table', function($q) use ($userId) {
                $q->whereHas('currentSession', function($sq) use ($userId) {
                    $sq->where('waiter_id', $userId)
                      ->where('status', 'ABIERTA');
                });
            })
            ->with(['table', 'table.sector'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'number' => $order->number,
                    'table_number' => $order->table->number,
                    'table_id' => $order->table->id,
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


<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use App\Events\KitchenOrderReady;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {
        $this->middleware('role:COCINA,ADMIN');
    }

    /**
     * Vista principal de cocina
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['ENVIADO', 'EN_PREPARACION', 'LISTO'])
            ->with(['table', 'items.product', 'items.modifiers']);

        // Filtrar por sector si se especifica
        if ($request->has('sector')) {
            // Aquí podrías filtrar por sector de cocina/barra si lo implementas
        }

        $orders = $query->orderBy('sent_at', 'asc')
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
     */
    public function markOrderReady(Order $order)
    {
        $order->items()->update(['status' => 'LISTO']);
        $order->update(['status' => 'LISTO']);

        // Disparar evento de notificación
        event(new KitchenOrderReady($order));

        return back()->with('success', 'Pedido marcado como listo');
    }
}


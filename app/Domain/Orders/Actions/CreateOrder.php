<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\CreateOrderData;
use App\Jobs\PrintKitchenTicket;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;

/**
 * Punto de entrada de dominio para crear pedidos (idempotente + atómico).
 */
final class CreateOrder
{
    public function __construct(private OrderService $orders) {}

    public function handle(CreateOrderData $data): Order
    {
        if ($data->idempotencyKey) {
            $existing = Order::withoutGlobalScopes()
                ->where('restaurant_id', $data->restaurantId)
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();

            if ($existing) {
                return $existing->loadMissing(['items.product', 'table']);
            }
        }

        $order = $this->orders->createOrder([
            'restaurant_id' => $data->restaurantId,
            'user_id' => $data->userId,
            'idempotency_key' => $data->idempotencyKey,
            'table_id' => $data->tableId,
            'subsector_item_id' => $data->subsectorItemId,
            'customer_name' => $data->customerName,
            'observations' => $data->observations,
            'items' => $data->items,
            'ensure_table_occupied' => $data->ensureTableOccupied,
        ]);

        if ($data->sendToKitchen) {
            try {
                $this->orders->sendToKitchen($order);
            } catch (\Throwable) {
                // Pedido ya existe; impresión/KDS no bloquean
            }

            DB::afterCommit(fn () => PrintKitchenTicket::dispatch($order->id)->onQueue('printing'));
        }

        return $order->fresh(['items.product', 'table', 'user']);
    }
}

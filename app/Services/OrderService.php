<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Crear un nuevo pedido
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Generar número de pedido único
            $orderNumber = $this->generateOrderNumber($data['restaurant_id']);

            // Crear el pedido
            $order = Order::create([
                'restaurant_id' => $data['restaurant_id'],
                'table_id' => $data['table_id'],
                'user_id' => $data['user_id'],
                'number' => $orderNumber,
                'status' => OrderStatus::ABIERTO->value,
                'observations' => $data['observations'] ?? null,
            ]);

            // Actualizar estado de la mesa
            $table = Table::find($data['table_id']);
            $table->update([
                'status' => 'OCUPADA',
                'current_order_id' => $order->id,
            ]);

            return $order;
        });
    }

    /**
     * Agregar item al pedido
     */
    public function addItem(Order $order, array $itemData): OrderItem
    {
        return DB::transaction(function () use ($order, $itemData) {
            $product = Product::findOrFail($itemData['product_id']);

            // Verificar stock si el producto lo requiere
            if ($product->has_stock) {
                $currentStock = $product->getCurrentStock($order->restaurant_id);
                if ($currentStock < $itemData['quantity']) {
                    throw new \Exception("Stock insuficiente. Disponible: {$currentStock}");
                }
            }

            // Crear el item
            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $itemData['quantity'],
                'unit_price' => $product->price,
                'subtotal' => $product->price * $itemData['quantity'],
                'observations' => $itemData['observations'] ?? null,
                'status' => 'PENDIENTE',
            ]);

            // Agregar modificadores si existen
            if (isset($itemData['modifiers']) && is_array($itemData['modifiers'])) {
                foreach ($itemData['modifiers'] as $modifierId) {
                    $modifier = $product->modifiers()->find($modifierId);
                    if ($modifier && $modifier->is_active) {
                        $orderItem->modifiers()->create([
                            'product_modifier_id' => $modifier->id,
                            'name' => $modifier->name,
                            'price_modifier' => $modifier->price_modifier,
                        ]);
                    }
                }
                $orderItem->calculateSubtotal();
            }

            // Recalcular total del pedido
            $order->calculateTotal();

            return $orderItem;
        });
    }

    /**
     * Enviar pedido a cocina
     */
    public function sendToKitchen(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            if ($order->status !== OrderStatus::ABIERTO->value) {
                throw new \Exception('Solo se pueden enviar pedidos abiertos');
            }

            $order->update([
                'status' => OrderStatus::ENVIADO->value,
                'sent_at' => now(),
            ]);

            // Actualizar estado de items a PENDIENTE
            $order->items()->update(['status' => 'PENDIENTE']);

            return $order->fresh();
        });
    }

    /**
     * Actualizar estado de item en cocina
     */
    public function updateItemStatus(OrderItem $item, string $status): OrderItem
    {
        $validStatuses = ['PENDIENTE', 'EN_PREPARACION', 'LISTO', 'ENTREGADO'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Estado inválido');
        }

        $item->update(['status' => $status]);

        // Si todos los items están listos, actualizar estado del pedido
        $order = $item->order;
        $allReady = $order->items()->where('status', '!=', 'ENTREGADO')->count() === 0;
        if ($allReady && $order->status === OrderStatus::EN_PREPARACION->value) {
            $order->update(['status' => OrderStatus::LISTO->value]);
        }

        return $item->fresh();
    }

    /**
     * Cerrar pedido
     * @param bool $freeTable Si es true, libera la mesa. Si es false, solo cierra el pedido.
     */
    public function closeOrder(Order $order, bool $freeTable = true): Order
    {
        return DB::transaction(function () use ($order, $freeTable) {
            if ($order->status === OrderStatus::CERRADO->value) {
                throw new \Exception('El pedido ya está cerrado');
            }

            $order->update([
                'status' => OrderStatus::CERRADO->value,
                'closed_at' => now(),
            ]);

            // Liberar la mesa solo si se solicita (por defecto sí, para compatibilidad)
            if ($freeTable) {
                $table = $order->table;
                if ($table) {
                    $table->update([
                        'status' => 'LIBRE',
                        'current_order_id' => null,
                    ]);
                }
            }

            return $order->fresh();
        });
    }

    /**
     * Generar número de pedido único
     */
    private function generateOrderNumber(int $restaurantId): string
    {
        $prefix = 'ORD-' . date('Y') . '-';
        $lastOrder = Order::where('restaurant_id', $restaurantId)
            ->where('number', 'like', $prefix . '%')
            ->orderBy('number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) str_replace($prefix, '', $lastOrder->number);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}


<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use App\Models\TableSession;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private StockService $stockService
    ) {
    }
    /**
     * Crear un nuevo pedido
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Generar número de pedido único
            $orderNumber = $this->generateOrderNumber($data['restaurant_id']);

            $table = null;
            $subsectorItem = null;
            $tableSessionId = null;

            // Manejar pedidos desde mesas o desde subsector items
            if (isset($data['table_id']) && $data['table_id']) {
                $table = Table::findOrFail($data['table_id']);

                // Asegurar sesión activa: si la mesa está OCUPADA pero no tiene sesión, crearla
                if ($table->status === Table::STATUS_OCUPADA && !$table->current_session_id) {
                    // Verificar que la tabla existe antes de crear sesión
                    if (!Schema::hasTable('table_sessions')) {
                        throw new \Exception('Faltan migraciones en la base de datos (table_sessions). Ejecutá migraciones para habilitar sesiones de mesa.');
                    }
                    
                    try {
                        $session = TableSession::create([
                            'restaurant_id' => $table->restaurant_id,
                            'table_id' => $table->id,
                            'started_at' => now(),
                        ]);
                        $table->update(['current_session_id' => $session->id]);
                    } catch (\Exception $e) {
                        \Log::error('Error al crear sesión de mesa en OrderService: ' . $e->getMessage());
                        throw new \Exception('Error al crear sesión de mesa. Verificá que las migraciones se hayan ejecutado correctamente.');
                    }
                }

                $tableSessionId = $table->current_session_id;
            } elseif (isset($data['subsector_item_id']) && $data['subsector_item_id']) {
                $subsectorItem = \App\Models\SubsectorItem::findOrFail($data['subsector_item_id']);
                
                // Crear sesión para el subsector item si no tiene una
                if (!$subsectorItem->current_session_id) {
                    if (!Schema::hasTable('table_sessions')) {
                        throw new \Exception('Faltan migraciones en la base de datos (table_sessions). Ejecutá migraciones para habilitar sesiones.');
                    }
                    
                    try {
                        $session = \App\Models\TableSession::create([
                            'restaurant_id' => $subsectorItem->subsector->restaurant_id,
                            'table_id' => null, // No hay mesa asociada
                            'started_at' => now(),
                            'status' => \App\Models\TableSession::STATUS_ABIERTA,
                        ]);
                        $subsectorItem->update(['current_session_id' => $session->id]);
                        $tableSessionId = $session->id;
                    } catch (\Exception $e) {
                        \Log::error('Error al crear sesión para subsector item: ' . $e->getMessage());
                        throw new \Exception('Error al crear sesión para el elemento del subsector.');
                    }
                } else {
                    $tableSessionId = $subsectorItem->current_session_id;
                }
            } else {
                // Pedido rápido sin mesa ni subsector (consumo inmediato desde caja)
                // No requiere table_session_id
                $tableSessionId = null;
            }

            // Crear el pedido
            $order = Order::create([
                'restaurant_id' => $data['restaurant_id'],
                'table_id' => $data['table_id'] ?? null,
                'subsector_item_id' => $data['subsector_item_id'] ?? null,
                'table_session_id' => $tableSessionId,
                'user_id' => $data['user_id'],
                'number' => $orderNumber,
                'status' => OrderStatus::ABIERTO->value,
                'observations' => $data['observations'] ?? null,
            ]);

            // Actualizar estado según el tipo
            if ($table) {
                $table->update([
                    'status' => 'OCUPADA',
                    'current_order_id' => $order->id,
                ]);
            } elseif ($subsectorItem) {
                $subsectorItem->update([
                    'status' => \App\Models\SubsectorItem::STATUS_OCUPADA,
                    'current_order_id' => $order->id,
                ]);
            }
            // Si no hay mesa ni subsector, es un pedido rápido (no actualizar nada)

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
                    throw new \Exception("Stock insuficiente para '{$product->name}'. Disponible: {$currentStock}, Solicitado: {$itemData['quantity']}");
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

            // Reducir stock inmediatamente cuando se agrega el item al pedido
            if ($product->has_stock) {
                $this->stockService->deductStockForSale(
                    $order->restaurant_id,
                    $product->id,
                    $itemData['quantity'],
                    $order->id
                );
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


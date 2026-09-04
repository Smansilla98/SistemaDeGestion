<?php

namespace App\Services;

use App\Domain\Money\Money;
use App\Domain\Numbering\SequenceGenerator;
use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SubsectorItem;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\User;
use App\Notifications\OrderCreatedNotification;
use App\Support\Concurrency;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OrderService
{
    public function __construct(
        private StockService $stockService,
        private SequenceGenerator $sequences,
    ) {}

    /**
     * Crear pedido (número bajo lock + items atómicos + idempotencia).
     *
     * @param  array{
     *   restaurant_id:int,
     *   user_id:int,
     *   table_id?:?int,
     *   subsector_item_id?:?int,
     *   observations?:?string,
     *   customer_name?:?string,
     *   idempotency_key?:?string,
     *   items?:array,
     *   ensure_table_occupied?:bool
     * }  $data
     */
    public function createOrder(array $data): Order
    {
        if (! empty($data['idempotency_key'])) {
            $existing = Order::withoutGlobalScopes()
                ->where('restaurant_id', $data['restaurant_id'])
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        try {
            return Concurrency::retryOnConflict(5, function () use ($data) {
                $orderNumber = $this->sequences->formatted(
                    (int) $data['restaurant_id'],
                    'ORD',
                    (int) date('Y')
                );

                $table = null;
                $subsectorItem = null;
                $tableSessionId = null;
                $ensureOccupied = (bool) ($data['ensure_table_occupied'] ?? true);

                if (! empty($data['table_id'])) {
                    $table = Table::lockForUpdate()->findOrFail($data['table_id']);

                    if ($ensureOccupied) {
                        $this->ensureTableReadyForOrder($table, (int) $data['user_id']);
                        $table->refresh();
                    }

                    if ($table->status === Table::STATUS_OCUPADA && ! $table->current_session_id) {
                        $session = $this->openTableSession($table, (int) $data['user_id']);
                        $table->update(['current_session_id' => $session->id]);
                        $tableSessionId = $session->id;
                    } else {
                        $tableSessionId = $table->current_session_id;
                    }
                } elseif (! empty($data['subsector_item_id'])) {
                    $subsectorItem = SubsectorItem::findOrFail($data['subsector_item_id']);

                    if (! $subsectorItem->current_session_id) {
                        $session = TableSession::create([
                            'restaurant_id' => $subsectorItem->subsector->restaurant_id,
                            'table_id' => null,
                            'started_at' => now(),
                            'status' => TableSession::STATUS_ABIERTA,
                            'waiter_id' => $data['user_id'],
                            'opened_by_user_id' => $data['user_id'],
                        ]);
                        $subsectorItem->update(['current_session_id' => $session->id]);
                        $tableSessionId = $session->id;
                    } else {
                        $tableSessionId = $subsectorItem->current_session_id;
                    }
                }

                $attrs = [
                    'restaurant_id' => $data['restaurant_id'],
                    'table_id' => $data['table_id'] ?? null,
                    'subsector_item_id' => $data['subsector_item_id'] ?? null,
                    'table_session_id' => $tableSessionId,
                    'user_id' => $data['user_id'],
                    'number' => $orderNumber,
                    'status' => OrderStatus::ABIERTO->value,
                    'observations' => $data['observations'] ?? null,
                    'customer_name' => $data['customer_name'] ?? null,
                ];

                if (! empty($data['idempotency_key']) && Schema::hasColumn('orders', 'idempotency_key')) {
                    $attrs['idempotency_key'] = $data['idempotency_key'];
                }

                $order = Order::create($attrs);

                if ($table) {
                    $table->update([
                        'status' => Table::STATUS_OCUPADA,
                        'current_order_id' => $order->id,
                    ]);
                } elseif ($subsectorItem) {
                    $subsectorItem->update([
                        'status' => SubsectorItem::STATUS_OCUPADA,
                        'current_order_id' => $order->id,
                    ]);
                }

                foreach ($data['items'] ?? [] as $itemData) {
                    $this->addItemWithinTransaction($order, $itemData);
                }

                $order->calculateTotal();

                User::where('restaurant_id', $order->restaurant_id)
                    ->where('is_active', true)
                    ->whereIn('role', ['COCINA', 'ADMIN'])
                    ->get()
                    ->each(fn ($u) => $u->notify(new OrderCreatedNotification($order)));

                return $order->fresh(['items']);
            });
        } catch (QueryException $e) {
            Log::error('Error SQL al crear pedido', [
                'restaurant_id' => $data['restaurant_id'] ?? null,
                'message' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'No pudimos abrir el pedido. Reintentá en unos segundos.',
                0,
                $e
            );
        }
    }

    /**
     * Sentar mesa + abrir sesión si hace falta (1 toque desde el pedido).
     */
    public function ensureTableReadyForOrder(Table $table, int $userId): void
    {
        if ($table->status === Table::STATUS_OCUPADA && $table->current_session_id) {
            $session = TableSession::find($table->current_session_id);
            if ($session && $session->isOpen()) {
                return;
            }
        }

        if (! Schema::hasTable('table_sessions')) {
            throw new \RuntimeException('Faltan migraciones (table_sessions).');
        }

        $session = $this->openTableSession($table, $userId);
        $table->update([
            'status' => Table::STATUS_OCUPADA,
            'current_session_id' => $session->id,
        ]);
    }

    private function openTableSession(Table $table, int $userId): TableSession
    {
        return TableSession::create([
            'restaurant_id' => $table->restaurant_id,
            'table_id' => $table->id,
            'started_at' => now(),
            'status' => TableSession::STATUS_ABIERTA,
            'waiter_id' => $userId,
            'opened_by_user_id' => $userId,
        ]);
    }

    public function addItem(Order $order, array $itemData): OrderItem
    {
        return DB::transaction(fn () => $this->addItemWithinTransaction($order, $itemData));
    }

    private function addItemWithinTransaction(Order $order, array $itemData): OrderItem
    {
        $product = Product::findOrFail($itemData['product_id']);

        if ($product->restaurant_id !== $order->restaurant_id) {
            throw new \RuntimeException("El producto '{$product->name}' no pertenece a este restaurante");
        }

        if (! $product->is_active) {
            throw new \RuntimeException("El producto '{$product->name}' no está activo");
        }

        if (! isset($itemData['quantity']) || $itemData['quantity'] < 1) {
            throw new \RuntimeException('La cantidad debe ser mayor a 0');
        }

        $qty = (int) $itemData['quantity'];
        $this->stockService->ensureStockForSale($order->restaurant_id, $product->id, $qty);

        $unit = Money::fromDecimal($product->price);
        $line = $unit->times($qty);

        $payload = [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'unit_price' => $unit->toDecimal(),
            'subtotal' => $line->toDecimal(),
            'observations' => $itemData['observations'] ?? null,
            'status' => OrderItem::STATUS_EN_PREPARACION,
        ];

        if (Schema::hasColumn('order_items', 'product_name_snapshot')) {
            $payload['product_name_snapshot'] = $product->name;
        }
        if (Schema::hasColumn('order_items', 'unit_price_cents')) {
            $payload['unit_price_cents'] = $unit->cents;
        }
        if (Schema::hasColumn('order_items', 'line_total_cents')) {
            $payload['line_total_cents'] = $line->cents;
        }

        $orderItem = OrderItem::create($payload);

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

        $this->stockService->deductStockForSale(
            $order->restaurant_id,
            $product->id,
            $qty,
            $order->id
        );

        $order->calculateTotal();

        return $orderItem;
    }

    public function sendToKitchen(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order->transitionTo(OrderStatus::ENVIADO, auth()->user(), 'Enviado a cocina');
            $order->forceFill(['sent_at' => now()])->saveQuietly();
            $order->items()->update(['status' => OrderItem::STATUS_EN_PREPARACION]);

            return $order->fresh();
        });
    }

    public function updateItemStatus(OrderItem $item, string $status): OrderItem
    {
        $validStatuses = ['EN_PREPARACION', 'LISTO', 'ENTREGADO'];
        if (! in_array($status, $validStatuses, true)) {
            throw new \RuntimeException('Estado inválido');
        }

        $item->update(['status' => $status]);

        $order = $item->order;
        $allReady = $order->items()->where('status', '!=', 'ENTREGADO')->count() === 0;
        if ($allReady && $order->status === OrderStatus::EN_PREPARACION->value) {
            $order->transitionTo(OrderStatus::LISTO, auth()->user(), 'Todos los ítems listos');
        }

        return $item->fresh();
    }

    public function removeOrderItems(Order $order, array $orderItemIds): void
    {
        DB::transaction(function () use ($order, $orderItemIds) {
            $items = OrderItem::where('order_id', $order->id)
                ->whereIn('id', $orderItemIds)
                ->get();

            foreach ($items as $item) {
                $this->stockService->restoreStockForSale(
                    $order->restaurant_id,
                    $item->product_id,
                    (int) $item->quantity,
                    $order->id
                );
                $item->delete();
            }

            $order->calculateTotal();

            AuditLog::record($order, 'item.voided', null, [
                'item_ids' => $orderItemIds,
            ], 'Ítems eliminados del pedido');
        });
    }

    /**
     * Eliminar pedido completo reponiendo stock.
     */
    public function deleteOrder(Order $order, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $reason) {
            $itemIds = $order->items()->pluck('id')->all();
            if ($itemIds) {
                $this->removeOrderItems($order, $itemIds);
            }

            if ($order->table_id && $order->table) {
                $table = $order->table;
                if ((int) $table->current_order_id === (int) $order->id) {
                    $table->update(['current_order_id' => null]);
                }
            }

            AuditLog::record($order, 'order.deleted', [
                'number' => $order->number,
                'status' => $order->status,
            ], null, $reason ?? 'Pedido eliminado');

            $order->delete();
        });
    }

    public function closeOrder(Order $order, bool $freeTable = true): Order
    {
        return DB::transaction(function () use ($order, $freeTable) {
            if ($order->status === OrderStatus::CERRADO->value) {
                throw new \RuntimeException('El pedido ya está cerrado');
            }

            $from = $order->statusEnum() ?? OrderStatus::ABIERTO;
            $order->forceFill([
                'status' => OrderStatus::CERRADO->value,
                'closed_at' => now(),
            ])->save();
            $order->recordStatusChange($from, OrderStatus::CERRADO, auth()->user(), 'Cierre de pedido');

            if ($freeTable) {
                $table = $order->table;
                if ($table) {
                    $table->update([
                        'status' => 'LIBRE',
                        'current_order_id' => null,
                    ]);
                }
            }

            AuditLog::record($order, 'order.closed', null, [
                'total' => $order->total,
            ], 'Pedido cerrado');

            return $order->fresh();
        });
    }
}

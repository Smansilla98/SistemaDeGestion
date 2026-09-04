<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private StockService $stockService
    ) {
    }
    /**
     * Crear un nuevo pedido.
     *
     * Reintenta si choca el unique de `number` (red de seguridad ante desync del contador).
     */
    public function createOrder(array $data): Order
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $orderNumber = $this->generateOrderNumber((int) $data['restaurant_id']);

                    $table = null;
                    $subsectorItem = null;
                    $tableSessionId = null;

                    if (isset($data['table_id']) && $data['table_id']) {
                        $table = Table::findOrFail($data['table_id']);

                        if ($table->status === Table::STATUS_OCUPADA && ! $table->current_session_id) {
                            if (! Schema::hasTable('table_sessions')) {
                                throw new \RuntimeException('Faltan migraciones en la base de datos (table_sessions). Ejecutá migraciones para habilitar sesiones de mesa.');
                            }

                            try {
                                $session = TableSession::create([
                                    'restaurant_id' => $table->restaurant_id,
                                    'table_id' => $table->id,
                                    'started_at' => now(),
                                ]);
                                $table->update(['current_session_id' => $session->id]);
                            } catch (\Exception $e) {
                                Log::error('Error al crear sesión de mesa en OrderService: '.$e->getMessage());
                                throw new \RuntimeException('Error al crear sesión de mesa. Verificá que las migraciones se hayan ejecutado correctamente.');
                            }
                        }

                        $tableSessionId = $table->current_session_id;
                    } elseif (isset($data['subsector_item_id']) && $data['subsector_item_id']) {
                        $subsectorItem = \App\Models\SubsectorItem::findOrFail($data['subsector_item_id']);

                        if (! $subsectorItem->current_session_id) {
                            if (! Schema::hasTable('table_sessions')) {
                                throw new \RuntimeException('Faltan migraciones en la base de datos (table_sessions). Ejecutá migraciones para habilitar sesiones.');
                            }

                            try {
                                $session = TableSession::create([
                                    'restaurant_id' => $subsectorItem->subsector->restaurant_id,
                                    'table_id' => null,
                                    'started_at' => now(),
                                    'status' => TableSession::STATUS_ABIERTA,
                                ]);
                                $subsectorItem->update(['current_session_id' => $session->id]);
                                $tableSessionId = $session->id;
                            } catch (\Exception $e) {
                                Log::error('Error al crear sesión para subsector item: '.$e->getMessage());
                                throw new \RuntimeException('Error al crear sesión para el elemento del subsector.');
                            }
                        } else {
                            $tableSessionId = $subsectorItem->current_session_id;
                        }
                    } else {
                        $tableSessionId = null;
                    }

                    $order = Order::create([
                        'restaurant_id' => $data['restaurant_id'],
                        'table_id' => $data['table_id'] ?? null,
                        'subsector_item_id' => $data['subsector_item_id'] ?? null,
                        'table_session_id' => $tableSessionId,
                        'user_id' => $data['user_id'],
                        'number' => $orderNumber,
                        'status' => OrderStatus::ABIERTO->value,
                        'observations' => $data['observations'] ?? null,
                        'customer_name' => $data['customer_name'] ?? null,
                    ]);

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

                    User::where('restaurant_id', $order->restaurant_id)
                        ->where('is_active', true)
                        ->whereIn('role', ['COCINA', 'ADMIN'])
                        ->get()
                        ->each(fn ($u) => $u->notify(new OrderCreatedNotification($order)));

                    return $order;
                });
            } catch (QueryException $e) {
                if ($this->isDuplicateOrderNumber($e) && $attempt < $maxAttempts) {
                    Log::warning('Colisión de número de pedido; reintentando', [
                        'restaurant_id' => $data['restaurant_id'] ?? null,
                        'attempt' => $attempt,
                    ]);
                    usleep(20_000 * $attempt);

                    continue;
                }

                Log::error('Error SQL al crear pedido', [
                    'restaurant_id' => $data['restaurant_id'] ?? null,
                    'sqlstate' => $e->errorInfo[0] ?? null,
                    'message' => $e->getMessage(),
                ]);

                throw new \RuntimeException(
                    'No pudimos abrir el pedido. Reintentá en unos segundos.',
                    0,
                    $e
                );
            }
        }

        throw new \RuntimeException('No pudimos abrir el pedido. Reintentá en unos segundos.');
    }

    /**
     * Agregar item al pedido
     */
    public function addItem(Order $order, array $itemData): OrderItem
    {
        return DB::transaction(function () use ($order, $itemData) {
            $product = Product::findOrFail($itemData['product_id']);

            // Validar que el producto pertenezca al restaurante
            if ($product->restaurant_id !== $order->restaurant_id) {
                throw new \Exception("El producto '{$product->name}' no pertenece a este restaurante");
            }

            // Validar que el producto esté activo
            if (!$product->is_active) {
                throw new \Exception("El producto '{$product->name}' no está activo");
            }

            // Validar cantidad
            if (!isset($itemData['quantity']) || $itemData['quantity'] < 1) {
                throw new \Exception("La cantidad debe ser mayor a 0");
            }

            // Verificar stock (producto con has_stock o receta con insumos)
            $this->stockService->ensureStockForSale($order->restaurant_id, $product->id, (int) $itemData['quantity']);

            // Crear el item
            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $itemData['quantity'],
                'unit_price' => $product->price,
                'subtotal' => $product->price * $itemData['quantity'],
                'observations' => $itemData['observations'] ?? null,
                'status' => 'EN_PREPARACION',
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

            // Reducir stock (insumos de la receta o producto con has_stock)
            $this->stockService->deductStockForSale(
                $order->restaurant_id,
                $product->id,
                (int) $itemData['quantity'],
                $order->id
            );

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

            // Actualizar estado de items a EN_PREPARACION
            $order->items()->update(['status' => 'EN_PREPARACION']);

            return $order->fresh();
        });
    }

    /**
     * Actualizar estado de item en cocina
     */
    public function updateItemStatus(OrderItem $item, string $status): OrderItem
    {
        $validStatuses = ['EN_PREPARACION', 'LISTO', 'ENTREGADO'];
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
     * Elimina items de un pedido y repone stock (invirtiendo lo que se hizo en addItem).
     *
     * Se asume que los $orderItemIds pertenecen al $order.
     */
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
        });
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
     * Generar número de pedido único por local y año.
     *
     * Debe llamarse dentro de la transacción de createOrder.
     * Usa order_counters + lockForUpdate, y se alinea con el máximo ya existente
     * en orders (evita desync tipo ORD-2026-1000 duplicado).
     */
    private function generateOrderNumber(int $restaurantId): string
    {
        $year = (int) date('Y');
        $prefix = 'ORD-'.$year.'-';
        $maxExisting = $this->maxSeqFromOrders($restaurantId, $year);

        // Crear fila de contador si no existe (seguro bajo concurrencia).
        // Si ya existe, no toca last_seq acá: el avance va con el lock.
        DB::table('order_counters')->insertOrIgnore([
            'restaurant_id' => $restaurantId,
            'year' => $year,
            'last_seq' => $maxExisting,
        ]);

        $counter = DB::table('order_counters')
            ->where('restaurant_id', $restaurantId)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if (! $counter) {
            // Carrera extrema: otro proceso borró la fila; recrear y bloquear.
            DB::table('order_counters')->insert([
                'restaurant_id' => $restaurantId,
                'year' => $year,
                'last_seq' => $maxExisting,
            ]);
            $counter = DB::table('order_counters')
                ->where('restaurant_id', $restaurantId)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $seq = max((int) $counter->last_seq, $maxExisting) + 1;

        DB::table('order_counters')
            ->where('restaurant_id', $restaurantId)
            ->where('year', $year)
            ->update(['last_seq' => $seq]);

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function maxSeqFromOrders(int $restaurantId, int $year): int
    {
        $prefix = 'ORD-'.$year.'-';
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $max = DB::table('orders')
                ->where('restaurant_id', $restaurantId)
                ->where('number', 'like', $prefix.'%')
                ->selectRaw('MAX(CAST(SUBSTRING(number, ?) AS UNSIGNED)) as max_seq', [strlen($prefix) + 1])
                ->value('max_seq');

            return (int) ($max ?? 0);
        }

        // SQLite / otros: parseo en PHP (tests).
        $max = 0;
        $numbers = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->where('number', 'like', $prefix.'%')
            ->pluck('number');

        foreach ($numbers as $number) {
            $suffix = substr((string) $number, strlen($prefix));
            if (ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $max;
    }

    private function isDuplicateOrderNumber(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? '';
        $message = $e->getMessage();

        return $sqlState === '23000'
            && (str_contains($message, 'orders_number_unique')
                || str_contains($message, 'orders_restaurant_id_number_unique')
                || (str_contains($message, 'Duplicate entry') && str_contains($message, 'ORD-')));
    }
}


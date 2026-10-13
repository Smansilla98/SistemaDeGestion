<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockReset;
use App\Models\SubsectorItem;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Puesta a punto (punto de partida).
 *
 * En una sola transacción: frena todos los pedidos en curso, baja lo activo
 * (sesiones y mesas) y deja el stock en las cantidades reales contadas.
 * No toca la caja: el arqueo sigue su circuito y los pagos ya cobrados quedan.
 */
final class StockResetService
{
    /** Texto que hay que escribir para confirmar la operación. */
    public const CONFIRMATION = 'PUESTA A PUNTO';

    /**
     * Qué se va a bajar y qué hay que contar, antes de ejecutar.
     *
     * @return array{
     *   active_orders:int, active_orders_total_cents:int, open_sessions:int, busy_tables:int,
     *   products: Collection<int, array{id:int, name:string, type:string, unit:?string, current_stock:int}>
     * }
     */
    public function preview(int $restaurantId): array
    {
        $activeOrders = $this->activeOrdersQuery($restaurantId);

        $products = $this->trackedProductsQuery($restaurantId)->orderBy('type')->orderBy('name')->get();
        $stockMap = Stock::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('product_id', $products->pluck('id'))
            ->pluck('quantity', 'product_id');

        return [
            'active_orders' => (clone $activeOrders)->count(),
            'active_orders_total_cents' => (int) (clone $activeOrders)->sum('total_cents'),
            'open_sessions' => TableSession::query()
                ->where('restaurant_id', $restaurantId)
                ->where('status', TableSession::STATUS_ABIERTA)
                ->count(),
            'busy_tables' => $this->busyTablesQuery($restaurantId)->count(),
            'products' => $products->map(fn (Product $p) => [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'type' => (string) $p->type,
                'unit' => $p->unit,
                'current_stock' => (int) ($stockMap[$p->id] ?? 0),
            ])->values(),
        ];
    }

    /**
     * Ejecuta la puesta a punto.
     *
     * @param  array<int|string, int|string>  $counts  product_id => cantidad real contada.
     *                                                 Lo que no viene en el conteo no se toca.
     */
    public function execute(int $restaurantId, User $user, array $counts, ?string $notes = null): StockReset
    {
        $counts = $this->normalizeCounts($counts);

        return DB::transaction(function () use ($restaurantId, $user, $counts, $notes) {
            $reset = StockReset::create([
                'restaurant_id' => $restaurantId,
                'user_id' => $user->id,
                'notes' => $notes,
            ]);

            [$ordersCancelled, $cancelledCents] = $this->cancelActiveOrders($restaurantId, $user, $reset);
            [$sessionsClosed, $tablesFreed] = $this->releaseTables($restaurantId);
            $productsAdjusted = $this->applyCounts($restaurantId, $user, $reset, $counts);

            $reset->update([
                'orders_cancelled' => $ordersCancelled,
                'orders_cancelled_total_cents' => $cancelledCents,
                'sessions_closed' => $sessionsClosed,
                'tables_freed' => $tablesFreed,
                'products_adjusted' => $productsAdjusted,
            ]);

            AuditLog::record($reset, 'stock.reset', null, [
                'orders_cancelled' => $ordersCancelled,
                'orders_cancelled_total_cents' => $cancelledCents,
                'sessions_closed' => $sessionsClosed,
                'tables_freed' => $tablesFreed,
                'products_adjusted' => $productsAdjusted,
            ], 'Puesta a punto', $restaurantId, $user->id);

            Cache::forget("dashboard_stats_{$restaurantId}");
            Cache::forget("top_products_today_{$restaurantId}");

            return $reset->fresh(['user']);
        });
    }

    /**
     * @return array{0:int, 1:int} cantidad de pedidos anulados y total (centavos) que quedó sin cobrar
     */
    private function cancelActiveOrders(int $restaurantId, User $user, StockReset $reset): array
    {
        $orders = $this->activeOrdersQuery($restaurantId)->lockForUpdate()->get();
        $cents = 0;

        foreach ($orders as $order) {
            $from = $order->statusEnum() ?? OrderStatus::ABIERTO;
            $cents += (int) ($order->total_cents ?? 0);

            // Es un corte forzado: no pasa por la máquina de estados (LISTO/ENTREGADO también se bajan)
            // ni repone stock, porque el stock queda fijado por el conteo.
            $order->forceFill([
                'status' => OrderStatus::CANCELADO->value,
                'closed_at' => now(),
            ])->save();
            $order->recordStatusChange($from, OrderStatus::CANCELADO, $user, "Puesta a punto #{$reset->id}");
        }

        return [$orders->count(), $cents];
    }

    /**
     * @return array{0:int, 1:int} sesiones cerradas y mesas liberadas
     */
    private function releaseTables(int $restaurantId): array
    {
        $sessionsClosed = TableSession::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', TableSession::STATUS_ABIERTA)
            ->update(['status' => TableSession::STATUS_CERRADA, 'ended_at' => now(), 'updated_at' => now()]);

        // Las reservas no son consumo en curso: se respetan.
        $tablesFreed = $this->busyTablesQuery($restaurantId)->update([
            'status' => Table::STATUS_LIBRE,
            'current_order_id' => null,
            'current_session_id' => null,
            'updated_at' => now(),
        ]);

        Table::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->where(fn ($q) => $q->whereNotNull('current_order_id')->orWhereNotNull('current_session_id'))
            ->update(['current_order_id' => null, 'current_session_id' => null]);

        SubsectorItem::query()
            ->whereHas('subsector', fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->where(fn ($q) => $q->where('status', '!=', SubsectorItem::STATUS_LIBRE)
                ->orWhereNotNull('current_order_id')
                ->orWhereNotNull('current_session_id'))
            ->update([
                'status' => SubsectorItem::STATUS_LIBRE,
                'current_order_id' => null,
                'current_session_id' => null,
            ]);

        return [$sessionsClosed, $tablesFreed];
    }

    /**
     * @param  array<int, int>  $counts
     */
    private function applyCounts(int $restaurantId, User $user, StockReset $reset, array $counts): int
    {
        if ($counts === []) {
            return 0;
        }

        $valid = $this->trackedProductsQuery($restaurantId)->whereIn('id', array_keys($counts))->pluck('id')->all();
        $unknown = array_diff(array_keys($counts), $valid);
        if ($unknown !== []) {
            throw new InvalidArgumentException('Hay productos en el conteo que no controlan stock en este local.');
        }

        $stocks = Stock::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('product_id', $valid)
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        $adjusted = 0;

        foreach ($counts as $productId => $counted) {
            $previous = (int) ($stocks[$productId]->quantity ?? 0);

            $reset->items()->create([
                'product_id' => $productId,
                'previous_quantity' => $previous,
                'counted_quantity' => $counted,
            ]);

            Stock::updateOrCreate(
                ['restaurant_id' => $restaurantId, 'product_id' => $productId],
                ['quantity' => $counted]
            );

            if ($previous === $counted) {
                continue;
            }

            // Se escribe directo (sin StockService::recordMovement) para no disparar
            // una notificación de stock bajo por cada producto del conteo.
            StockMovement::create([
                'restaurant_id' => $restaurantId,
                'product_id' => $productId,
                'user_id' => $user->id,
                'type' => StockMovement::TYPE_AJUSTE,
                'quantity' => abs($counted - $previous),
                'previous_stock' => $previous,
                'new_stock' => $counted,
                'reason' => "Puesta a punto #{$reset->id}",
                'reference' => "stock_reset_{$reset->id}",
            ]);
            $adjusted++;
        }

        return $adjusted;
    }

    /**
     * @param  array<int|string, mixed>  $counts
     * @return array<int, int>
     */
    private function normalizeCounts(array $counts): array
    {
        $clean = [];
        foreach ($counts as $productId => $quantity) {
            if ($quantity === null || $quantity === '') {
                continue;
            }
            if (! is_numeric($quantity) || (int) $quantity < 0 || (int) $productId <= 0) {
                throw new InvalidArgumentException('Las cantidades contadas deben ser números enteros mayores o iguales a cero.');
            }
            $clean[(int) $productId] = (int) $quantity;
        }

        return $clean;
    }

    private function activeOrdersQuery(int $restaurantId)
    {
        return Order::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->whereNotIn('status', [OrderStatus::CERRADO->value, OrderStatus::CANCELADO->value]);
    }

    private function busyTablesQuery(int $restaurantId)
    {
        return Table::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', [Table::STATUS_OCUPADA, Table::STATUS_CERRADA]);
    }

    /**
     * Todo lo que lleva stock: productos con control de stock y todos los insumos.
     */
    private function trackedProductsQuery(int $restaurantId)
    {
        return Product::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('has_stock', true)->orWhere('type', 'INSUMO'));
    }
}

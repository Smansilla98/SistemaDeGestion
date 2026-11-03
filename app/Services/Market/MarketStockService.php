<?php

declare(strict_types=1);

namespace App\Services\Market;

use App\Models\ConsignmentItem;
use App\Models\MarketProduct;
use App\Models\MarketStockMovement;
use App\Models\User;
use App\Services\Community\OrganizationDirectory;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Único camino para cambiar el stock de mercado. Cada cambio bloquea la fila del producto,
 * deja un movimiento con el saldo resultante y nunca deja stock negativo.
 *
 * Una venta consume primero la mercadería en consignación (la más vieja primero) y así
 * cada consignación sabe cuánto vendió y cuánto dinero se le debe al emprendedor.
 */
final class MarketStockService
{
    public function __construct(private readonly Notifier $notifier, private readonly OrganizationDirectory $directory) {}

    public function move(MarketProduct $product, string $type, float $quantity, ?User $by, ?string $note = null, ?int $consignmentItemId = null, ?int $cycleOrderId = null): MarketStockMovement
    {
        if (! array_key_exists($type, MarketStockMovement::TYPES) || abs($quantity) < 0.0005) {
            throw ValidationException::withMessages(['quantity' => 'Movimiento inválido.']);
        }

        return DB::transaction(function () use ($product, $type, $quantity, $by, $note, $consignmentItemId, $cycleOrderId) {
            $locked = MarketProduct::withoutGlobalScopes()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $before = (float) $locked->stock_quantity;
            $after = round($before + $quantity, 3);
            if ($after < 0) {
                throw ValidationException::withMessages(['quantity' => "Stock insuficiente de {$locked->name}: hay ".self::qty($before)." {$locked->unit}."]);
            }
            $locked->forceFill(['stock_quantity' => $after])->save();
            $product->setRawAttributes($locked->getAttributes(), true);

            $movement = MarketStockMovement::query()->create([
                'restaurant_id' => $locked->restaurant_id, 'market_product_id' => $locked->id, 'type' => $type,
                'quantity' => $quantity, 'balance_after' => $after, 'consignment_item_id' => $consignmentItemId,
                'cycle_order_id' => $cycleOrderId, 'note' => $note, 'user_id' => $by?->id,
            ]);

            if ($locked->min_stock !== null && $after <= (float) $locked->min_stock && $before > (float) $locked->min_stock) {
                DB::afterCommit(fn () => $this->notifier->sendMany(
                    $this->directory->usersWithRoles((int) $locked->restaurant_id, [User::ROLE_ADMIN, User::ROLE_COORDINADOR, User::ROLE_INVENTARIO]),
                    'stock', "Stock bajo: {$locked->name}", 'Quedan '.self::qty($after)." {$locked->unit} (mínimo ".self::qty((float) $locked->min_stock).').',
                    route('market.products.show', $locked->id), "market-low:{$locked->id}:{$movement->id}", (int) $locked->restaurant_id));
            }

            return $movement;
        });
    }

    /**
     * Registra una venta: primero descuenta de consignaciones abiertas (FIFO), después del stock propio.
     *
     * @return list<MarketStockMovement>
     */
    public function sell(MarketProduct $product, float $quantity, ?User $by, ?string $note = null, ?int $cycleOrderId = null): array
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'La cantidad debe ser mayor a cero.']);
        }

        return DB::transaction(function () use ($product, $quantity, $by, $note, $cycleOrderId) {
            $locked = MarketProduct::withoutGlobalScopes()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            if ((float) $locked->stock_quantity + 0.0005 < $quantity) {
                throw ValidationException::withMessages(['quantity' => "Stock insuficiente de {$locked->name}: hay ".self::qty((float) $locked->stock_quantity)." {$locked->unit}."]);
            }

            $movements = [];
            $remaining = $quantity;
            $items = ConsignmentItem::query()->where('market_product_id', $locked->id)
                ->whereHas('consignment', fn ($q) => $q->withoutGlobalScopes()->where('status', 'ABIERTA'))
                ->orderBy('id')->lockForUpdate()->get();
            foreach ($items as $item) {
                $take = min($remaining, $item->pending());
                if ($take <= 0) {
                    continue;
                }
                $item->update([
                    'qty_sold' => round($item->qty_sold + $take, 3),
                    'sold_cents' => $item->sold_cents + (int) round($take * $item->unit_price_cents),
                ]);
                $movements[] = $this->move($locked, 'VENTA', -$take, $by, $note, $item->id, $cycleOrderId);
                $remaining = round($remaining - $take, 3);
                if ($remaining <= 0) {
                    break;
                }
            }
            if ($remaining > 0) {
                $movements[] = $this->move($locked, 'VENTA', -$remaining, $by, $note, null, $cycleOrderId);
            }
            $product->setRawAttributes($locked->fresh()->getAttributes(), true);

            return $movements;
        });
    }

    /** Devuelve al emprendedor mercadería consignada que no se vendió. */
    public function returnToEntrepreneur(ConsignmentItem $item, float $quantity, ?User $by, ?string $note = null): MarketStockMovement
    {
        return DB::transaction(function () use ($item, $quantity, $by, $note) {
            $locked = ConsignmentItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            if ($quantity <= 0 || $quantity > $locked->pending() + 0.0005) {
                throw ValidationException::withMessages(['quantity' => 'Se pueden devolver hasta '.self::qty($locked->pending()).' unidades pendientes.']);
            }
            $locked->update(['qty_returned' => round($locked->qty_returned + $quantity, 3)]);
            $item->setRawAttributes($locked->getAttributes(), true);
            $product = MarketProduct::withoutGlobalScopes()->findOrFail($locked->market_product_id);

            return $this->move($product, 'DEVOLUCION', -$quantity, $by, $note, $locked->id);
        });
    }

    public static function qty(float $q): string
    {
        return rtrim(rtrim(number_format($q, 3, ',', '.'), '0'), ',');
    }
}

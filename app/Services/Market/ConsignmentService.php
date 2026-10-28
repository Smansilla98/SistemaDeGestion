<?php

declare(strict_types=1);

namespace App\Services\Market;

use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\ConsignmentSettlement;
use App\Models\Entrepreneur;
use App\Models\MarketProduct;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Consignaciones: entrada de mercadería ajena, saldo de unidades y de dinero, y liquidación.
 *
 * Stock físico (MarketStockService), propiedad (de qué emprendedor es cada ítem) y dinero
 * adeudado (vendido − comisión − ya liquidado) se calculan por separado.
 */
final class ConsignmentService
{
    public function __construct(private readonly MarketStockService $stock, private readonly Notifier $notifier) {}

    /**
     * @param  list<array{market_product_id: int, quantity: float|int|string, unit_price_cents?: int|null}>  $items
     */
    public function receive(Entrepreneur $entrepreneur, string $receivedOn, float $commissionPercent, array $items, User $by, ?string $notes = null): Consignment
    {
        if ($commissionPercent < 0 || $commissionPercent > 100) {
            throw ValidationException::withMessages(['commission_percent' => 'La comisión va de 0 a 100.']);
        }
        $items = array_values(array_filter($items, fn ($i) => (float) $i['quantity'] > 0));
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Cargá al menos un producto con cantidad.']);
        }

        $consignment = DB::transaction(function () use ($entrepreneur, $receivedOn, $commissionPercent, $items, $by, $notes) {
            $consignment = Consignment::query()->create([
                'restaurant_id' => $entrepreneur->restaurant_id, 'entrepreneur_id' => $entrepreneur->id, 'received_on' => $receivedOn,
                'commission_percent' => $commissionPercent, 'status' => 'ABIERTA', 'notes' => $notes, 'created_by' => $by->id,
            ]);
            foreach ($items as $i) {
                $product = MarketProduct::query()->where('restaurant_id', $entrepreneur->restaurant_id)
                    ->where('entrepreneur_id', $entrepreneur->id)->find((int) $i['market_product_id']);
                if (! $product) {
                    throw ValidationException::withMessages(['items' => 'Hay un producto que no es de este emprendedor.']);
                }
                $qty = round((float) $i['quantity'], 3);
                $item = $consignment->items()->create([
                    'market_product_id' => $product->id, 'qty_delivered' => $qty,
                    'unit_price_cents' => isset($i['unit_price_cents']) && $i['unit_price_cents'] !== null ? (int) $i['unit_price_cents'] : $product->price_cents,
                ]);
                $this->stock->move($product, 'CONSIGNACION', $qty, $by, "Consignación #{$consignment->id}", $item->id);
            }

            return $consignment;
        });

        if ($entrepreneur->user) {
            $this->notifier->send($entrepreneur->user, 'consignaciones', 'Recibimos tu mercadería en consignación',
                'Consignación #'.$consignment->id.' del '.$consignment->received_on->format('d/m/Y').'.',
                route('market.consignments.show', $consignment), "consignment:{$consignment->id}", (int) $entrepreneur->restaurant_id);
        }

        return $consignment;
    }

    /**
     * @return array{delivered: float, sold: float, returned: float, pending_qty: float, gross_cents: int, commission_cents: int,
     *               net_cents: int, settled_gross_cents: int, settled_commission_cents: int, paid_cents: int, owed_cents: int}
     */
    public function balance(Consignment $consignment): array
    {
        $items = $consignment->items()->get();
        $gross = (int) $items->sum('sold_cents');
        $commission = (int) round($gross * (float) $consignment->commission_percent / 100);
        $settlements = $consignment->settlements()->get();
        $paid = (int) $settlements->sum('paid_cents');

        return [
            'delivered' => round((float) $items->sum('qty_delivered'), 3),
            'sold' => round((float) $items->sum('qty_sold'), 3),
            'returned' => round((float) $items->sum('qty_returned'), 3),
            'pending_qty' => round((float) $items->sum(fn (ConsignmentItem $i) => $i->pending()), 3),
            'gross_cents' => $gross,
            'commission_cents' => $commission,
            'net_cents' => $gross - $commission,
            'settled_gross_cents' => (int) $settlements->sum('gross_cents'),
            'settled_commission_cents' => (int) $settlements->sum('commission_cents'),
            'paid_cents' => $paid,
            'owed_cents' => $gross - $commission - $paid,
        ];
    }

    /** Liquida al emprendedor todo lo vendido y todavía no pagado. */
    public function settle(Consignment $consignment, string $paidOn, ?string $method, ?string $note, User $by): ConsignmentSettlement
    {
        $settlement = DB::transaction(function () use ($consignment, $paidOn, $method, $note, $by) {
            Consignment::withoutGlobalScopes()->whereKey($consignment->id)->lockForUpdate()->first();
            $b = $this->balance($consignment);
            if ($b['owed_cents'] <= 0) {
                throw ValidationException::withMessages(['settlement' => 'No hay ventas pendientes de liquidar.']);
            }
            $settlement = ConsignmentSettlement::query()->create([
                'restaurant_id' => $consignment->restaurant_id, 'consignment_id' => $consignment->id,
                'gross_cents' => $b['gross_cents'] - $b['settled_gross_cents'],
                'commission_cents' => $b['commission_cents'] - $b['settled_commission_cents'],
                'paid_cents' => $b['owed_cents'], 'paid_on' => $paidOn, 'method' => $method, 'note' => $note, 'created_by' => $by->id,
            ]);
            if ($b['pending_qty'] <= 0) {
                $consignment->update(['status' => 'CERRADA']);
            }

            return $settlement;
        });

        $user = $consignment->entrepreneur?->user;
        if ($user) {
            $this->notifier->send($user, 'consignaciones', 'Liquidación de tu consignación',
                'Te pagamos $'.number_format($settlement->paid_cents / 100, 2, ',', '.').' por la consignación #'.$consignment->id.'.',
                route('market.consignments.show', $consignment), "settlement:{$settlement->id}", (int) $consignment->restaurant_id);
        }

        return $settlement;
    }

    /** Cierra una consignación sin unidades pendientes ni dinero adeudado. */
    public function closeIfDone(Consignment $consignment): bool
    {
        $b = $this->balance($consignment);
        if ($b['pending_qty'] <= 0 && $b['owed_cents'] <= 0) {
            $consignment->update(['status' => 'CERRADA']);

            return true;
        }

        return false;
    }
}

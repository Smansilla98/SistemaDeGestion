<?php

declare(strict_types=1);

namespace App\Services\Market;

use App\Models\Contact;
use App\Models\CycleOrder;
use App\Models\MarketProduct;
use App\Models\OrderCycle;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ciclos de pedidos de un nodo o mercado: se toman pedidos mientras está abierto, al cerrar
 * se consolida qué pedir a cada productor y al entregar se descuenta el stock.
 */
final class OrderCycleService
{
    public function __construct(private readonly MarketStockService $stock, private readonly Notifier $notifier) {}

    /**
     * @param  array<int|string, float|int|string|null>  $quantities  market_product_id => cantidad
     */
    public function placeOrder(OrderCycle $cycle, Contact $contact, array $quantities, bool $manager = false, ?string $note = null): CycleOrder
    {
        if (! $cycle->acceptsOrders() && ! ($manager && $cycle->status !== 'ENTREGADO')) {
            throw ValidationException::withMessages(['cycle' => 'El ciclo no está tomando pedidos.']);
        }
        if ((int) $contact->restaurant_id !== (int) $cycle->restaurant_id) {
            throw ValidationException::withMessages(['contact_id' => 'El contacto no es de esta organización.']);
        }
        $lines = collect($quantities)->map(fn ($q) => round((float) $q, 3))->filter(fn ($q) => $q > 0);
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'El pedido está vacío.']);
        }

        return DB::transaction(function () use ($cycle, $contact, $lines, $note) {
            $order = CycleOrder::query()->firstOrNew(['order_cycle_id' => $cycle->id, 'contact_id' => $contact->id]);
            if ($order->exists && $order->status !== 'PENDIENTE') {
                throw ValidationException::withMessages(['items' => 'Ese pedido ya fue '.mb_strtolower(CycleOrder::LABELS[$order->status] ?? $order->status).'.']);
            }
            $order->fill(['status' => 'PENDIENTE', 'note' => $note])->save();
            $order->items()->delete();

            $products = MarketProduct::query()->where('restaurant_id', $cycle->restaurant_id)->where('is_active', true)
                ->whereIn('id', $lines->keys()->map(fn ($k) => (int) $k))->get()->keyBy('id');
            $total = 0;
            foreach ($lines as $productId => $qty) {
                $product = $products->get((int) $productId);
                if (! $product) {
                    throw ValidationException::withMessages(['items' => 'Hay un producto que no está disponible.']);
                }
                $line = (int) round($qty * $product->price_cents);
                $order->items()->create(['market_product_id' => $product->id, 'quantity' => $qty, 'unit_price_cents' => $product->price_cents, 'line_cents' => $line]);
                $total += $line;
            }
            $order->update(['total_cents' => $total]);

            return $order->fresh('items');
        });
    }

    public function deliver(CycleOrder $order, User $by, ?int $paidCents = null): CycleOrder
    {
        if ($order->status !== 'PENDIENTE') {
            throw ValidationException::withMessages(['order' => 'El pedido no está pendiente.']);
        }

        DB::transaction(function () use ($order, $by, $paidCents) {
            foreach ($order->items()->with('product')->get() as $item) {
                $this->stock->sell($item->product, (float) $item->quantity, $by, "Pedido ciclo #{$order->order_cycle_id}", $order->id);
            }
            $order->update(['status' => 'ENTREGADO', 'delivered_at' => now(), 'paid_cents' => $paidCents ?? $order->total_cents]);
        });

        $user = $order->contact?->user;
        if ($user) {
            $this->notifier->send($user, 'pedidos', 'Tu pedido fue entregado', 'Pedido del ciclo «'.$order->cycle->name.'».', null,
                "cycle-order-delivered:{$order->id}", (int) $order->cycle->restaurant_id);
        }

        return $order->refresh();
    }

    public function cancel(CycleOrder $order): void
    {
        if ($order->status !== 'PENDIENTE') {
            throw ValidationException::withMessages(['order' => 'Sólo se cancelan pedidos pendientes.']);
        }
        $order->update(['status' => 'CANCELADO']);
    }

    /**
     * Total a pedir por producto (agrupado por emprendedor) de los pedidos no cancelados.
     *
     * @return Collection<int, array{product: MarketProduct, quantity: float, total_cents: int, stock: float}>
     */
    public function consolidated(OrderCycle $cycle): Collection
    {
        $rows = DB::table('cycle_order_items')
            ->join('cycle_orders', 'cycle_orders.id', '=', 'cycle_order_items.cycle_order_id')
            ->where('cycle_orders.order_cycle_id', $cycle->id)->where('cycle_orders.status', '!=', 'CANCELADO')
            ->groupBy('cycle_order_items.market_product_id')
            ->selectRaw('cycle_order_items.market_product_id as pid, SUM(cycle_order_items.quantity) as qty, SUM(cycle_order_items.line_cents) as cents')
            ->get();
        $products = MarketProduct::query()->with('entrepreneur')->whereIn('id', $rows->pluck('pid'))->get()->keyBy('id');

        $out = collect();
        foreach ($rows as $r) {
            $product = $products->get($r->pid);
            if ($product instanceof MarketProduct) {
                $out->push(['product' => $product, 'quantity' => round((float) $r->qty, 3),
                    'total_cents' => (int) $r->cents, 'stock' => (float) $product->stock_quantity]);
            }
        }

        return $out->sortBy(fn ($r) => ($r['product']->entrepreneur?->business_name ?? '').$r['product']->name)->values();
    }
}

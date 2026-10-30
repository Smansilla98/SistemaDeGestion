<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\SpecialMenu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Menús especiales de la barra: cuál rige hoy, qué se puede vender, a qué precio y
 * si el stock alcanza para lo previsto.
 */
final class SpecialMenuService
{
    /** @var array<string, SpecialMenu|null> */
    private array $active = [];

    public function __construct(private readonly StockService $stock) {}

    /** Menú que rige en la fecha (hoy por defecto). Si se superponen, el que empezó último. */
    public function activeFor(int $restaurantId, ?\DateTimeInterface $date = null): ?SpecialMenu
    {
        $day = ($date ?? now())->format('Y-m-d');
        $key = $restaurantId.'|'.$day;
        if (! array_key_exists($key, $this->active)) {
            $this->active[$key] = SpecialMenu::withoutGlobalScopes()->where('restaurant_id', $restaurantId)
                ->whereDate('starts_on', '<=', $day)->whereDate('ends_on', '>=', $day)
                ->with('items')->orderByDesc('starts_on')->orderByDesc('id')->first();
        }

        return $this->active[$key];
    }

    public function forget(): void
    {
        $this->active = [];
    }

    /**
     * Limita una consulta de productos a lo que se puede vender hoy (menú exclusivo).
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function restrict(Builder $query, int $restaurantId): Builder
    {
        $menu = $this->activeFor($restaurantId);
        if ($menu?->exclusive) {
            $query->whereIn('products.id', $menu->items->pluck('product_id'));
        }

        return $query;
    }

    /**
     * Pone el precio del menú en los productos listados (sólo para mostrar; no se guarda).
     *
     * @template T of iterable<Product>
     *
     * @param  T  $products
     * @return T
     */
    public function applyPrices(iterable $products, int $restaurantId): iterable
    {
        $menu = $this->activeFor($restaurantId);
        if (! $menu) {
            return $products;
        }
        $prices = $menu->items->whereNotNull('price')->pluck('price', 'product_id');
        foreach ($products as $product) {
            if ($prices->has($product->id)) {
                $product->setAttribute('regular_price', $product->price);
                $product->setAttribute('price', $prices->get($product->id));
            }
        }

        return $products;
    }

    /** Precio de venta hoy: el del menú especial si lo tiene, si no el de carta. */
    public function priceFor(Product $product, int $restaurantId): string
    {
        $item = $this->activeFor($restaurantId)?->items->firstWhere('product_id', $product->id);

        return (string) ($item?->price ?? $product->price);
    }

    /** Falla si hay un menú exclusivo y el producto no está en él. */
    public function ensureSellable(Product $product, int $restaurantId): void
    {
        $menu = $this->activeFor($restaurantId);
        if ($menu?->exclusive && ! $menu->items->contains('product_id', $product->id)) {
            throw new \RuntimeException("«{$product->name}» no está en el menú especial de hoy ({$menu->name}).");
        }
    }

    /**
     * Stock necesario para lo previsto del menú, por insumo o producto, contra lo disponible.
     *
     * @return Collection<int, array{product: Product, needed: int, unit: string, for: non-empty-array<int, string>, available: int, missing: int}>
     */
    public function stockPlan(SpecialMenu $menu): Collection
    {
        $rows = [];
        foreach ($menu->items()->with('product.ingredients')->get() as $item) {
            if (! $item->product || ! $item->planned_quantity) {
                continue;
            }
            foreach ($this->stock->plannedConsumption($item->product, $item->planned_quantity) as $id => $line) {
                $rows[$id] ??= ['product' => $line['product'], 'needed' => 0, 'unit' => $line['unit'], 'for' => []];
                $rows[$id]['needed'] += $line['quantity'];
                $rows[$id]['for'][] = $item->product->name;
            }
        }

        /** @var Collection<int, array{product: Product, needed: int, unit: string, for: non-empty-array<int, string>, available: int, missing: int}> $out */
        $out = collect($rows)->map(function (array $r) use ($menu) {
            $available = $r['product']->getCurrentStock((int) $menu->restaurant_id);

            return $r + ['available' => $available, 'missing' => max(0, $r['needed'] - $available)];
        })->sortByDesc('missing')->values();

        return $out;
    }
}

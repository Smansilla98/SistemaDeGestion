<?php

namespace App\Services\Events;

use App\Models\Event;
use App\Models\Stock;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Productos que un evento programado necesita y no alcanzan con el stock actual.
 * Lo muestran la Agenda (vista mensual) y el calendario de eventos.
 */
class EventStockAlerts
{
    /**
     * @param  iterable<Event>  $events  con `products` cargados
     * @return list<array{event: Event, product: \App\Models\Product, current_stock: float, expected_quantity: float, shortage: float}>
     */
    public function forEvents(int $restaurantId, iterable $events): array
    {
        $alerts = [];
        foreach ($events as $event) {
            foreach ($event->products as $product) {
                $current = (float) (Stock::query()->where('restaurant_id', $restaurantId)->where('product_id', $product->id)->value('quantity') ?? 0);
                $expected = (float) ($product->pivot->expected_quantity ?? 0);
                if ($current < $expected) {
                    $alerts[] = ['event' => $event, 'product' => $product, 'current_stock' => $current,
                        'expected_quantity' => $expected, 'shortage' => $expected - $current];
                }
            }
        }

        return $alerts;
    }

    /** @return list<array{event: Event, product: \App\Models\Product, current_stock: float, expected_quantity: float, shortage: float}> */
    public function between(int $restaurantId, CarbonInterface $from, CarbonInterface $to): array
    {
        /** @var Collection<int, Event> $events */
        $events = Event::query()->where('restaurant_id', $restaurantId)->where('status', Event::STATUS_PROGRAMADO)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])->with('products')->orderBy('date')->get();

        return $this->forEvents($restaurantId, $events);
    }
}

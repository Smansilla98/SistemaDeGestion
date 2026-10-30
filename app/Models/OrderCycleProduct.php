<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Producto ofrecido en un ciclo del nodo, con el precio y el cupo de ese ciclo.
 */
class OrderCycleProduct extends Model
{
    protected $fillable = ['order_cycle_id', 'market_product_id', 'price_cents', 'max_quantity'];

    protected $casts = ['price_cents' => 'integer', 'max_quantity' => 'float'];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(OrderCycle::class, 'order_cycle_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketProduct::class, 'market_product_id');
    }
}

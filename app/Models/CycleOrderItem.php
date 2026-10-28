<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CycleOrderItem extends Model
{
    protected $fillable = ['cycle_order_id', 'market_product_id', 'quantity', 'unit_price_cents', 'line_cents'];

    protected $casts = ['quantity' => 'float', 'unit_price_cents' => 'integer', 'line_cents' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketProduct::class, 'market_product_id');
    }
}

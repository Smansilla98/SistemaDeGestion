<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockResetItem extends Model
{
    protected $fillable = [
        'stock_reset_id',
        'product_id',
        'previous_quantity',
        'counted_quantity',
    ];

    protected $casts = [
        'previous_quantity' => 'integer',
        'counted_quantity' => 'integer',
    ];

    public function stockReset(): BelongsTo
    {
        return $this->belongsTo(StockReset::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

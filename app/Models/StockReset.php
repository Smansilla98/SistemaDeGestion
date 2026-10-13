<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Puesta a punto: corte general del local + conteo real de stock.
 */
class StockReset extends Model
{
    use Concerns\BelongsToRestaurant;

    protected $fillable = [
        'restaurant_id',
        'user_id',
        'orders_cancelled',
        'orders_cancelled_total_cents',
        'sessions_closed',
        'tables_freed',
        'products_adjusted',
        'notes',
    ];

    protected $casts = [
        'orders_cancelled' => 'integer',
        'orders_cancelled_total_cents' => 'integer',
        'sessions_closed' => 'integer',
        'tables_freed' => 'integer',
        'products_adjusted' => 'integer',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockResetItem::class);
    }
}

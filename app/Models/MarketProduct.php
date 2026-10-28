<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producto de un emprendedor (o de la propia organización si entrepreneur_id es null).
 * stock_quantity es un saldo cacheado: sólo lo cambia MarketStockService.
 */
class MarketProduct extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'entrepreneur_id', 'category', 'name', 'unit', 'price_cents', 'min_stock', 'is_active'];

    protected $casts = ['price_cents' => 'integer', 'stock_quantity' => 'float', 'min_stock' => 'float', 'is_active' => 'boolean'];

    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(Entrepreneur::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(MarketStockMovement::class)->latest('id');
    }

    public function isLow(): bool
    {
        return $this->min_stock !== null && $this->stock_quantity <= $this->min_stock;
    }
}

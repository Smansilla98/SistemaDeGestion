<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketStockMovement extends Model
{
    use BelongsToRestaurant;

    public const TYPES = ['INGRESO' => 'Ingreso', 'CONSIGNACION' => 'Entrada en consignación', 'VENTA' => 'Venta',
        'DEVOLUCION' => 'Devolución al emprendedor', 'AJUSTE' => 'Ajuste de inventario'];

    protected $fillable = ['restaurant_id', 'market_product_id', 'type', 'quantity', 'balance_after', 'consignment_item_id', 'cycle_order_id', 'note', 'user_id'];

    protected $casts = ['quantity' => 'float', 'balance_after' => 'float'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketProduct::class, 'market_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

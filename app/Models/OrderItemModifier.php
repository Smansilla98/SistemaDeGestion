<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemModifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'product_modifier_id',
        'name',
        'price_modifier',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
    ];

    /**
     * Relación: Un modificador de item pertenece a un item
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Relación: Un modificador de item puede pertenecer a un modificador de producto
     */
    public function productModifier(): BelongsTo
    {
        return $this->belongsTo(ProductModifier::class);
    }
}



<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'price',
        'image',
        'has_stock',
        'stock_minimum',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'has_stock' => 'boolean',
        'stock_minimum' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relación: Un producto pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un producto pertenece a una categoría
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación: Un producto tiene muchos modificadores
     */
    public function modifiers(): HasMany
    {
        return $this->hasMany(ProductModifier::class);
    }

    /**
     * Relación: Un producto tiene muchas entradas de pedido
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relación: Un producto tiene stock
     */
    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Relación: Un producto tiene movimientos de stock
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Obtener el stock actual del producto en un restaurante
     */
    public function getCurrentStock(int $restaurantId): int
    {
        $stock = Stock::where('restaurant_id', $restaurantId)
            ->where('product_id', $this->id)
            ->first();

        return $stock ? $stock->quantity : 0;
    }
}



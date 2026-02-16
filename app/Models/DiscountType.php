<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountType extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'name',
        'percentage',
        'description',
        'is_active',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relación: Un tipo de descuento pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Calcular el monto del descuento basado en un subtotal
     */
    public function calculateDiscount($subtotal): float
    {
        return round($subtotal * ($this->percentage / 100), 2);
    }
}


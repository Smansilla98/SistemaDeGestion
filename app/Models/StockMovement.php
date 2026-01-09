<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    // Tipos de movimiento
    const TYPE_ENTRADA = 'ENTRADA';
    const TYPE_SALIDA = 'SALIDA';
    const TYPE_AJUSTE = 'AJUSTE';

    protected $fillable = [
        'restaurant_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'previous_stock',
        'new_stock',
        'reason',
        'reference',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
    ];

    /**
     * Relación: Un movimiento pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un movimiento pertenece a un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Un movimiento pertenece a un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener todos los tipos disponibles
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_ENTRADA,
            self::TYPE_SALIDA,
            self::TYPE_AJUSTE,
        ];
    }
}



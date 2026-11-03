<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
     *
     * @return BelongsTo<Restaurant, $this>
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un movimiento pertenece a un producto
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Un movimiento pertenece a un usuario
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Un movimiento puede tener una compra asociada (solo para ENTRADAS)
     *
     * @return HasOne<Purchase, $this>
     */
    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class);
    }

    /**
     * Verificar si el movimiento es una entrada
     */
    public function isEntry(): bool
    {
        return $this->type === self::TYPE_ENTRADA;
    }

    /**
     * Verificar si el movimiento es una salida
     */
    public function isExit(): bool
    {
        return $this->type === self::TYPE_SALIDA;
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

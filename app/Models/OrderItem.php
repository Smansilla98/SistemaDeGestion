<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    // Estados del item
    const STATUS_PENDIENTE = 'PENDIENTE';
    const STATUS_EN_PREPARACION = 'EN_PREPARACION';
    const STATUS_LISTO = 'LISTO';
    const STATUS_ENTREGADO = 'ENTREGADO';

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'observations',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relación: Un item pertenece a un pedido
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relación: Un item pertenece a un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Un item tiene muchos modificadores
     */
    public function modifiers(): HasMany
    {
        return $this->hasMany(OrderItemModifier::class);
    }

    /**
     * Obtener todos los estados disponibles
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDIENTE,
            self::STATUS_EN_PREPARACION,
            self::STATUS_LISTO,
            self::STATUS_ENTREGADO,
        ];
    }

    /**
     * Calcular subtotal del item (precio * cantidad + modificadores)
     */
    public function calculateSubtotal(): void
    {
        $modifiersTotal = $this->modifiers()->sum('price_modifier');
        $itemSubtotal = ($this->unit_price * $this->quantity) + ($modifiersTotal * $this->quantity);
        $this->subtotal = $itemSubtotal;
        $this->save();
    }
}



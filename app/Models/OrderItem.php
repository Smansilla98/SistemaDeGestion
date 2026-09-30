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
        'product_name_snapshot',
        'quantity',
        'unit_price',
        'unit_price_cents',
        'subtotal',
        'line_total_cents',
        'observations',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'unit_price_cents' => 'integer',
        'subtotal' => 'decimal:2',
        'line_total_cents' => 'integer',
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
        $modifiersTotal = (float) $this->modifiers()->sum('price_modifier');
        $unit = (float) $this->unit_price;
        $qty = (int) $this->quantity;
        $itemSubtotal = ($unit * $qty) + ($modifiersTotal * $qty);
        $this->subtotal = $itemSubtotal;

        if (\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'unit_price_cents')) {
            $this->unit_price_cents = (int) round($unit * 100);
            $this->line_total_cents = (int) round($itemSubtotal * 100);
        }

        $this->save();
    }
}

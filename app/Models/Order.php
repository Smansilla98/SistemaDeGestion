<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    // Estados del pedido
    const STATUS_ABIERTO = 'ABIERTO';
    const STATUS_ENVIADO = 'ENVIADO';
    const STATUS_EN_PREPARACION = 'EN_PREPARACION';
    const STATUS_LISTO = 'LISTO';
    const STATUS_ENTREGADO = 'ENTREGADO';
    const STATUS_CERRADO = 'CERRADO';
    const STATUS_CANCELADO = 'CANCELADO';

    protected $fillable = [
        'restaurant_id',
        'table_id',
        'table_session_id',
        'user_id',
        'number',
        'status',
        'subtotal',
        'discount',
        'total',
        'observations',
        'sent_at',
        'closed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'sent_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Relación: Un pedido pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un pedido pertenece a una mesa
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function tableSession(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'table_session_id');
    }

    /**
     * Relación: Un pedido pertenece a un usuario (mozo)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Un pedido tiene muchos items
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relación: Un pedido tiene muchos pagos
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Obtener todos los estados disponibles
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ABIERTO,
            self::STATUS_ENVIADO,
            self::STATUS_EN_PREPARACION,
            self::STATUS_LISTO,
            self::STATUS_ENTREGADO,
            self::STATUS_CERRADO,
            self::STATUS_CANCELADO,
        ];
    }

    /**
     * Verificar si el pedido está abierto
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ABIERTO;
    }

    /**
     * Verificar si el pedido está cerrado
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CERRADO;
    }

    /**
     * Calcular total del pedido
     */
    public function calculateTotal(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $this->subtotal = $subtotal;
        $this->total = $subtotal - $this->discount;
        $this->save();
    }
}



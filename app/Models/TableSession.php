<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableSession extends Model
{
    use HasFactory;

    // Estados de sesión
    const STATUS_ABIERTA = 'ABIERTA';
    const STATUS_CERRADA = 'CERRADA';
    
    // Mantener compatibilidad con código antiguo
    const STATUS_OPEN = 'ABIERTA';
    const STATUS_CLOSED = 'CERRADA';

    protected $fillable = [
        'restaurant_id',
        'table_id',
        'waiter_id',
        'opened_by_user_id',
        'started_at',
        'ended_at',
        'status',
        'total_amount',
        'paid_at',
        'payment_method',
        'cash_register_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'paid_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_session_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'table_session_id');
    }

    /**
     * Relación: Una sesión pertenece a una caja
     */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    /**
     * Verificar si la sesión está activa (abierta y sin cerrar)
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ABIERTA && $this->ended_at === null;
    }

    /**
     * Verificar si la sesión está abierta
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ABIERTA;
    }

    /**
     * Verificar si la sesión está cerrada
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CERRADA;
    }
    
    /**
     * Calcular total de la sesión desde sus pedidos
     */
    public function calculateTotal(): float
    {
        return $this->orders()->sum('total');
    }
    
    /**
     * Verificar si la sesión está pagada
     */
    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }
}



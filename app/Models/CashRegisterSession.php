<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegisterSession extends Model
{
    use HasFactory;

    // Estados de la sesión
    const STATUS_ABIERTA = 'ABIERTA';
    const STATUS_CERRADA = 'CERRADA';

    protected $fillable = [
        'restaurant_id',
        'cash_register_id',
        'user_id',
        'initial_amount',
        'final_amount',
        'expected_amount',
        'difference',
        'opened_at',
        'closed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Relación: Una sesión pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Una sesión pertenece a una caja
     */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    /**
     * Relación: Una sesión pertenece a un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Una sesión tiene muchos pagos
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relación: Una sesión tiene muchos movimientos de caja
     */
    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    /**
     * Obtener todos los estados disponibles
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ABIERTA,
            self::STATUS_CERRADA,
        ];
    }

    /**
     * Verificar si la sesión está abierta
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ABIERTA;
    }
}



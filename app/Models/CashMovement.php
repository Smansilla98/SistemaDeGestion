<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    use HasFactory;

    // Tipos de movimiento
    const TYPE_INGRESO = 'INGRESO';
    const TYPE_EGRESO = 'EGRESO';

    protected $fillable = [
        'restaurant_id',
        'cash_register_session_id',
        'user_id',
        'type',
        'amount',
        'description',
        'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Relación: Un movimiento pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un movimiento pertenece a una sesión de caja
     */
    public function cashRegisterSession(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class);
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
            self::TYPE_INGRESO,
            self::TYPE_EGRESO,
        ];
    }
}



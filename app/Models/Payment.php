<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    // Métodos de pago
    const METHOD_EFECTIVO = 'EFECTIVO';
    const METHOD_DEBITO = 'DEBITO';
    const METHOD_CREDITO = 'CREDITO';
    const METHOD_TRANSFERENCIA = 'TRANSFERENCIA';

    protected $fillable = [
        'restaurant_id',
        'order_id',
        'cash_register_session_id',
        'user_id',
        'payment_method',
        'amount',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Relación: Un pago pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un pago pertenece a un pedido
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relación: Un pago pertenece a una sesión de caja
     */
    public function cashRegisterSession(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class);
    }

    /**
     * Relación: Un pago pertenece a un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener todos los métodos de pago disponibles
     */
    public static function getMethods(): array
    {
        return [
            self::METHOD_EFECTIVO,
            self::METHOD_DEBITO,
            self::METHOD_CREDITO,
            self::METHOD_TRANSFERENCIA,
        ];
    }
}



<?php

namespace App\Models;

use App\Domain\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use Concerns\BelongsToRestaurant;
    use HasFactory;

    // Métodos de pago
    const METHOD_EFECTIVO = 'EFECTIVO';

    const METHOD_DEBITO = 'DEBITO';

    const METHOD_CREDITO = 'CREDITO';

    const METHOD_TRANSFERENCIA = 'TRANSFERENCIA';

    protected $fillable = [
        'restaurant_id',
        'order_id',
        'table_session_id',
        'cash_register_session_id',
        'user_id',
        'payment_method',
        'amount',
        'amount_cents',
        'reference',
        'operation_number',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_cents' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Payment $payment) {
            if ($payment->isDirty('amount') && ! $payment->isDirty('amount_cents')) {
                $payment->amount_cents = Money::fromDecimal($payment->amount ?? 0)->cents;
            } elseif ($payment->isDirty('amount_cents') && ! $payment->isDirty('amount')) {
                $payment->amount = round(((int) $payment->amount_cents) / 100, 2);
            } elseif ($payment->amount !== null && $payment->amount_cents === null) {
                $payment->amount_cents = Money::fromDecimal($payment->amount)->cents;
            }
        });
    }

    /**
     * Relación: Un pago pertenece a un restaurante
     *
     * @return BelongsTo<Restaurant, $this>
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un pago pertenece a un pedido
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relación: Un pago pertenece a una sesión de caja
     *
     * @return BelongsTo<CashRegisterSession, $this>
     */
    public function cashRegisterSession(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class);
    }

    /**
     * Relación: Un pago pertenece a un usuario
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Un pago pertenece a una sesión de mesa
     *
     * @return BelongsTo<TableSession, $this>
     */
    public function tableSession(): BelongsTo
    {
        return $this->belongsTo(TableSession::class);
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

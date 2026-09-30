<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Cómo cobra este restaurante con cada medio (alias/CVU de transferencia,
 * imagen de QR estático). No es un método de pago en sí — eso lo sigue
 * registrando Payment::payment_method en cada cobro. Ver la migración de
 * creación para el porqué de no incluir MIXTO acá.
 */
class PaymentMethodConfiguration extends Model
{
    use Concerns\BelongsToRestaurant;
    use HasFactory;

    const TYPE_EFECTIVO = 'EFECTIVO';

    const TYPE_DEBITO = 'DEBITO';

    const TYPE_CREDITO = 'CREDITO';

    const TYPE_TRANSFERENCIA = 'TRANSFERENCIA';

    const TYPE_QR = 'QR';

    protected $fillable = [
        'restaurant_id',
        'type',
        'label',
        'is_active',
        'sort_order',
        'alias',
        'cvu',
        'cbu',
        'account_holder',
        'cuit',
        'qr_image_path',
        'instructions',
        'provider',
        'external_reference',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['qr_image_url'];

    public static function types(): array
    {
        return [
            self::TYPE_EFECTIVO,
            self::TYPE_DEBITO,
            self::TYPE_CREDITO,
            self::TYPE_TRANSFERENCIA,
            self::TYPE_QR,
        ];
    }

    public static function defaultLabels(): array
    {
        return [
            self::TYPE_EFECTIVO => 'Efectivo',
            self::TYPE_DEBITO => 'Tarjeta de débito',
            self::TYPE_CREDITO => 'Tarjeta de crédito',
            self::TYPE_TRANSFERENCIA => 'Transferencia',
            self::TYPE_QR => 'QR',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function getQrImageUrlAttribute(): ?string
    {
        return $this->qr_image_path ? Storage::disk('public')->url($this->qr_image_path) : null;
    }
}

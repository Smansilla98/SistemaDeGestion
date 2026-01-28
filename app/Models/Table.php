<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    use HasFactory;

    // Estados de la mesa
    const STATUS_LIBRE = 'LIBRE';
    const STATUS_OCUPADA = 'OCUPADA';
    const STATUS_RESERVADA = 'RESERVADA';
    const STATUS_CERRADA = 'CERRADA';

    protected $fillable = [
        'restaurant_id',
        'sector_id',
        'number',
        'capacity',
        'position_x',
        'position_y',
        'status',
        'current_order_id',
        'current_session_id',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
    ];

    /**
     * Relación: Una mesa pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Una mesa pertenece a un sector
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Relación: Una mesa tiene un pedido actual
     */
    public function currentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    /**
     * Relación: la sesión actual (si existe)
     */
    public function currentSession(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'current_session_id');
    }

    /**
     * Relación: Una mesa tiene muchos pedidos
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relación: Una mesa tiene muchas sesiones
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(TableSession::class);
    }

    /**
     * Obtener todos los estados disponibles
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_LIBRE,
            self::STATUS_OCUPADA,
            self::STATUS_RESERVADA,
            self::STATUS_CERRADA,
        ];
    }
}



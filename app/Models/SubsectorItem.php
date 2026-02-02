<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubsectorItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'subsector_id',
        'name',
        'position',
        'status',
        'current_order_id',
        'current_session_id',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    const STATUS_LIBRE = 'LIBRE';
    const STATUS_OCUPADA = 'OCUPADA';
    const STATUS_RESERVADA = 'RESERVADA';
    const STATUS_CERRADA = 'CERRADA';

    /**
     * Relación: Un item pertenece a un subsector
     */
    public function subsector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'subsector_id');
    }

    /**
     * Relación: Un item puede tener un pedido actual
     */
    public function currentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    /**
     * Relación: Un item puede tener una sesión actual
     */
    public function currentSession(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'current_session_id');
    }

    /**
     * Relación: Un item puede tener muchos pedidos
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'subsector_item_id');
    }

    /**
     * Verificar si está libre
     */
    public function isLibre(): bool
    {
        return $this->status === self::STATUS_LIBRE;
    }

    /**
     * Verificar si está ocupada
     */
    public function isOcupada(): bool
    {
        return $this->status === self::STATUS_OCUPADA;
    }
}


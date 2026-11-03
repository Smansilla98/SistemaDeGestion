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
     *
     * @return BelongsTo<Sector, $this>
     */
    public function subsector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'subsector_id');
    }

    /**
     * Relación: Un item puede tener un pedido actual
     *
     * @return BelongsTo<Order, $this>
     */
    public function currentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    /**
     * Relación: Un item puede tener una sesión actual
     *
     * @return BelongsTo<TableSession, $this>
     */
    public function currentSession(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'current_session_id');
    }

    /**
     * Relación: Un item puede tener muchos pedidos
     *
     * @return HasMany<Order, $this>
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

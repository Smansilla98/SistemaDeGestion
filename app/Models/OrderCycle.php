<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ciclo de pedidos (nodo agroecológico / mercado): se abre, se cierra y se entrega en una fecha.
 */
class OrderCycle extends Model
{
    use BelongsToRestaurant;

    public const STATUSES = ['ABIERTO' => 'Abierto', 'CERRADO' => 'Cerrado', 'ENTREGADO' => 'Entregado'];

    protected $fillable = ['restaurant_id', 'name', 'opens_at', 'closes_at', 'delivery_on', 'status', 'notes'];

    protected $casts = ['opens_at' => 'datetime', 'closes_at' => 'datetime', 'delivery_on' => 'date'];

    public function orders(): HasMany
    {
        return $this->hasMany(CycleOrder::class);
    }

    /** Productos que se pueden pedir en este ciclo (se cargan antes de abrir). */
    public function offer(): HasMany
    {
        return $this->hasMany(OrderCycleProduct::class);
    }

    public function acceptsOrders(): bool
    {
        return $this->status === 'ABIERTO' && now()->between($this->opens_at, $this->closes_at);
    }
}

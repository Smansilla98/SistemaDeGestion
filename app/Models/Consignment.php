<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mercadería que un emprendedor deja en consignación: sigue siendo suya hasta venderse.
 * Por ítem: entregado = vendido + devuelto + pendiente. El dinero se liquida aparte.
 */
class Consignment extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'entrepreneur_id', 'received_on', 'commission_percent', 'status', 'notes', 'created_by'];

    protected $casts = ['received_on' => 'date', 'commission_percent' => 'float'];

    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(Entrepreneur::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsignmentItem::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(ConsignmentSettlement::class);
    }
}

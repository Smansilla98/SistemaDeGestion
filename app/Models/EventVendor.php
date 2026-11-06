<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Puesto de un evento: un emprendedor (o un parrillero suelto) vende lo suyo y le deja al espacio un
 * porcentaje de lo que vendió. El espacio no carga stock de eso: sólo registra cuánto vendió y cobra su parte.
 */
class EventVendor extends Model
{
    use BelongsToRestaurant;

    public const CATEGORIES = ['COMIDA' => 'Comida', 'PARRILLA' => 'Parrilla', 'BEBIDA' => 'Bebidas', 'OTRO' => 'Otro'];

    protected $fillable = ['restaurant_id', 'event_id', 'entrepreneur_id', 'name', 'category', 'percent', 'sales_cents', 'collected_cents', 'notes'];

    protected $casts = ['percent' => 'float', 'sales_cents' => 'integer', 'collected_cents' => 'integer'];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<Entrepreneur, $this> */
    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(Entrepreneur::class);
    }

    /** Lo que le corresponde al espacio: el porcentaje de lo que vendió el puesto. */
    public function commissionCents(): int
    {
        return (int) round((int) $this->sales_cents * $this->percent / 100);
    }

    public function pendingCents(): int
    {
        return max(0, $this->commissionCents() - $this->collected_cents);
    }
}

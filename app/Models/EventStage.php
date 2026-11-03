<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Etapa de un evento (armado, prensa, show, desarme…) con responsable y presupuesto.
 * Lo pagado sale de los gastos del evento imputados a la etapa.
 */
class EventStage extends Model
{
    public const STATUSES = ['PENDIENTE' => 'Pendiente', 'EN_CURSO' => 'En curso', 'HECHA' => 'Hecha'];

    protected $fillable = ['event_id', 'name', 'position', 'starts_at', 'responsible_contact_id', 'budget_cents', 'status', 'notes'];

    protected $casts = ['starts_at' => 'datetime', 'budget_cents' => 'integer'];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<Contact, $this> */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'responsible_contact_id');
    }

    /** @return HasMany<ActivityEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(ActivityEntry::class);
    }
}

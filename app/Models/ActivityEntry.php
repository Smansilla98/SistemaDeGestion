<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ingreso o gasto de un taller o evento. No se borra: se anula (voided_at) para conservar el historial.
 */
class ActivityEntry extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'entryable_type', 'entryable_id', 'type', 'concept', 'amount_cents', 'date',
        'contact_id', 'workshop_enrollment_id', 'event_stage_id', 'method', 'recorded_by', 'voided_at', 'void_reason'];

    protected $casts = ['date' => 'date', 'amount_cents' => 'integer', 'voided_at' => 'datetime'];

    public function entryable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(EventStage::class, 'event_stage_id');
    }
}

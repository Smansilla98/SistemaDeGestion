<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Clase concreta de un taller. Reprogramar o cancelar cambia esta fila, no el horario:
 * original_date conserva la fecha prevista.
 */
class WorkshopSession extends Model
{
    public const PROGRAMADA = 'PROGRAMADA';

    public const REPROGRAMADA = 'REPROGRAMADA';

    public const CANCELADA = 'CANCELADA';

    protected $fillable = ['workshop_id', 'workshop_schedule_id', 'date', 'start_time', 'end_time', 'status', 'original_date', 'note'];

    protected $casts = ['date' => 'date', 'original_date' => 'date'];

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(WorkshopAttendance::class);
    }
}

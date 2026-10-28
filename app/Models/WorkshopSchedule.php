<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopSchedule extends Model
{
    public const WEEKDAYS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    protected $fillable = ['workshop_id', 'weekday', 'start_time', 'end_time'];

    protected $casts = ['weekday' => 'integer'];

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function label(): string
    {
        return (self::WEEKDAYS[$this->weekday] ?? '?').' '.substr((string) $this->start_time, 0, 5).'–'.substr((string) $this->end_time, 0, 5);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Menú especial de la barra entre dos fechas (inclusive). Si es exclusivo, mientras dura
 * sólo se venden sus productos; si no, la carta sigue igual y estos usan su precio especial.
 */
class SpecialMenu extends Model
{
    use BelongsToRestaurant;

    protected $fillable = ['restaurant_id', 'name', 'starts_on', 'ends_on', 'exclusive', 'notes', 'agenda_notice_id', 'created_by'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'exclusive' => 'boolean'];

    protected static function booted(): void
    {
        $forget = fn () => app(\App\Services\SpecialMenuService::class)->forget();
        static::saved($forget);
        static::deleted($forget);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SpecialMenuItem::class);
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(AgendaNotice::class, 'agenda_notice_id');
    }

    public function isActiveOn(\DateTimeInterface $date): bool
    {
        $d = $date->format('Y-m-d');

        return $this->starts_on->format('Y-m-d') <= $d && $this->ends_on->format('Y-m-d') >= $d;
    }
}

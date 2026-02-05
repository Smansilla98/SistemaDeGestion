<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'created_by',
        'name',
        'description',
        'date',
        'time',
        'expected_attendance',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'expected_attendance' => 'integer',
    ];

    // Estados del evento
    const STATUS_PROGRAMADO = 'PROGRAMADO';
    const STATUS_EN_CURSO = 'EN_CURSO';
    const STATUS_FINALIZADO = 'FINALIZADO';
    const STATUS_CANCELADO = 'CANCELADO';

    /**
     * Relación: Un evento pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un evento fue creado por un usuario
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación: Un evento tiene muchos productos (con cantidad esperada)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'event_products')
            ->withPivot('expected_quantity', 'actual_quantity', 'notes')
            ->withTimestamps();
    }

    /**
     * Relación: Un evento tiene muchos event_products
     */
    public function eventProducts(): HasMany
    {
        return $this->hasMany(EventProduct::class);
    }

    /**
     * Verificar si el evento es hoy
     */
    public function isToday(): bool
    {
        return $this->date->isToday();
    }

    /**
     * Verificar si el evento es en el futuro
     */
    public function isFuture(): bool
    {
        if ($this->date->isFuture()) {
            return true;
        }
        if ($this->date->isToday() && $this->time) {
            $time = is_string($this->time) ? Carbon::parse($this->time) : Carbon::parse($this->time);
            return now()->format('H:i') < $time->format('H:i');
        }
        return false;
    }

    /**
     * Verificar si el evento es en el pasado
     */
    public function isPast(): bool
    {
        if ($this->date->isPast()) {
            return true;
        }
        if ($this->date->isToday() && $this->time) {
            $time = is_string($this->time) ? Carbon::parse($this->time) : Carbon::parse($this->time);
            return now()->format('H:i') > $time->format('H:i');
        }
        return false;
    }

    /**
     * Obtener fecha y hora formateada
     */
    public function getFormattedDateTimeAttribute(): string
    {
        $date = $this->date->format('d/m/Y');
        if ($this->time) {
            $time = is_string($this->time) ? $this->time : Carbon::parse($this->time)->format('H:i');
            return $date . ' ' . $time . 'hs';
        }
        return $date;
    }
}


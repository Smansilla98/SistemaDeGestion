<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
        'house_sells',
        'distribution_rule_id',
        'activity_category_id',
    ];

    protected $casts = [
        'date' => 'date',
        'expected_attendance' => 'integer',
        'house_sells' => 'array',
    ];

    // Estados del evento
    const STATUS_PROGRAMADO = 'PROGRAMADO';

    const STATUS_EN_CURSO = 'EN_CURSO';

    const STATUS_FINALIZADO = 'FINALIZADO';

    const STATUS_CANCELADO = 'CANCELADO';

    /**
     * Relación: Un evento pertenece a un restaurante
     *
     * @return BelongsTo<Restaurant, $this>
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un evento fue creado por un usuario
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación: Un evento tiene muchos productos (con cantidad esperada)
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'event_products')
            ->withPivot('expected_quantity', 'actual_quantity', 'notes')
            ->withTimestamps();
    }

    /**
     * Relación: Un evento tiene muchos event_products
     *
     * @return HasMany<EventProduct, $this>
     */
    public function eventProducts(): HasMany
    {
        return $this->hasMany(EventProduct::class);
    }

    /**
     * Etapas del evento (responsable, presupuesto, estado).
     *
     * @return HasMany<EventStage, $this>
     */
    public function stages(): HasMany
    {
        return $this->hasMany(EventStage::class)->orderBy('position')->orderBy('id');
    }

    /** @return BelongsTo<DistributionRule, $this> */
    public function distributionRule(): BelongsTo
    {
        return $this->belongsTo(DistributionRule::class);
    }

    /** @return MorphMany<ActivityEntry, $this> */
    public function entries(): MorphMany
    {
        return $this->morphMany(ActivityEntry::class, 'entryable');
    }

    /** @return MorphMany<DistributionPayout, $this> */
    public function payouts(): MorphMany
    {
        return $this->morphMany(DistributionPayout::class, 'payoutable');
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

            return $date.' '.$time.'hs';
        }

        return $date;
    }

    /** @return BelongsTo<ActivityCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class, 'activity_category_id');
    }

    /**
     * Puestos de emprendedores que venden en el evento y le dejan un porcentaje al espacio.
     *
     * @return HasMany<EventVendor, $this>
     */
    public function vendors(): HasMany
    {
        return $this->hasMany(EventVendor::class)->orderBy('id');
    }
}

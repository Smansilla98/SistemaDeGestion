<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'parent_id',
        'name',
        'description',
        'layout_config',
        'type',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    const TYPE_SECTOR = 'SECTOR';

    const TYPE_SUBSECTOR = 'SUBSECTOR';

    /**
     * Relación: Un sector pertenece a un restaurante
     *
     * @return BelongsTo<Restaurant, $this>
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un sector tiene muchas mesas
     *
     * @return HasMany<Table, $this>
     */
    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }

    /**
     * Relación: Un sector puede tener un sector padre (si es subsector)
     *
     * @return BelongsTo<Sector, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'parent_id');
    }

    /**
     * Relación: Un sector puede tener muchos subsectores
     *
     * @return HasMany<Sector, $this>
     */
    public function subsectors(): HasMany
    {
        return $this->hasMany(Sector::class, 'parent_id')->where('type', self::TYPE_SUBSECTOR);
    }

    /**
     * Relación: Un subsector tiene muchos items (lugares, elementos)
     *
     * @return HasMany<SubsectorItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SubsectorItem::class, 'subsector_id');
    }

    /**
     * Relación: Un sector tiene muchas categorías
     *
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'sector_id');
    }

    /**
     * Verificar si es un subsector
     */
    public function isSubsector(): bool
    {
        return $this->type === self::TYPE_SUBSECTOR;
    }

    /**
     * Verificar si es un sector principal
     */
    public function isMainSector(): bool
    {
        return $this->type === self::TYPE_SECTOR && $this->parent_id === null;
    }
}

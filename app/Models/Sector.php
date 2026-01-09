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
        'name',
        'description',
        'layout_config',
        'is_active',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relación: Un sector pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Un sector tiene muchas mesas
     */
    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }
}



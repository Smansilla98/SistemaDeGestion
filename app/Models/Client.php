<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entidad cliente del restaurante (datos de contacto y notas).
 * La persistencia principal para la API REST usa repositorios PDO; este modelo
 * permite usar Eloquent en vistas o futuras integraciones.
 */
class Client extends Model
{
    protected $fillable = [
        'restaurant_id',
        'name',
        'phone',
        'email',
        'notes',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}

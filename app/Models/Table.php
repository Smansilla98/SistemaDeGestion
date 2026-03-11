<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    use HasFactory;

    // Estados de la mesa
    const STATUS_LIBRE = 'LIBRE';
    const STATUS_OCUPADA = 'OCUPADA';
    const STATUS_RESERVADA = 'RESERVADA';
    const STATUS_CERRADA = 'CERRADA';

    protected $fillable = [
        'restaurant_id',
        'sector_id',
        'number',
        'capacity',
        'position_x',
        'position_y',
        'status',
        'current_order_id',
        'current_session_id',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
    ];

    /**
     * Relación: Una mesa pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Relación: Una mesa pertenece a un sector
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Relación: Una mesa tiene un pedido actual
     */
    public function currentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    /**
     * Relación: la sesión actual (si existe)
     */
    public function currentSession(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'current_session_id');
    }

    /**
     * Relación: Una mesa tiene muchos pedidos
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relación: Una mesa tiene muchas sesiones
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(TableSession::class);
    }

    /**
     * Obtener todos los estados disponibles
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_LIBRE,
            self::STATUS_OCUPADA,
            self::STATUS_RESERVADA,
            self::STATUS_CERRADA,
        ];
    }

    /**
     * Parte numérica inicial del número de mesa (para orden 0-9, 10-19, etc.).
     * Ej: "1" -> 1, "1-b" -> 1, "10" -> 10, "11 bis" -> 11.
     */
    public function getNumericSortKey(): int
    {
        $number = trim($this->number ?? '');
        if ($number === '') {
            return 0;
        }
        if (preg_match('/^(\d+)/', $number, $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    /**
     * Ordenar una colección de mesas por grupos numéricos (0-9, 10-19, …) y luego por número completo.
     */
    public static function sortByNumericGroup($tables)
    {
        return $tables->sortBy(function (Table $table) {
            return [$table->getNumericSortKey(), $table->number];
        })->values();
    }
}



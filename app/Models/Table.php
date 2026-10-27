<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    use Concerns\BelongsToRestaurant;
    use HasFactory;

    // Estados de la mesa
    const STATUS_LIBRE = 'LIBRE';

    const STATUS_OCUPADA = 'OCUPADA';

    const STATUS_RESERVADA = 'RESERVADA';

    const STATUS_CERRADA = 'CERRADA';

    /** Roles que toman pedidos desde una mesa (pantalla de mesas y plano). */
    public const ORDER_TAKER_ROLES = ['SUPERADMIN', 'ADMIN', 'GERENTE', 'ENCARGADO', 'MOZO'];

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
     * Nombre para mostrar: "Mesa 12" si es un número, o el nombre tal cual ("Barra 1").
     */
    public function displayName(): string
    {
        $number = trim((string) $this->number);

        return preg_match('/^\d/', $number) ? 'Mesa '.$number : $number;
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

    const SORT_NUMBER = 'number';

    const SORT_STATUS = 'status';

    const SORT_CAPACITY = 'capacity';

    /**
     * Criterios de orden que puede elegir el usuario (web y app usan los mismos).
     *
     * @return array<string, string>
     */
    public static function sortOptions(): array
    {
        return [
            self::SORT_NUMBER => 'Número de mesa',
            self::SORT_STATUS => 'Ocupadas primero',
            self::SORT_CAPACITY => 'Capacidad',
        ];
    }

    public static function normalizeSort(?string $sort): string
    {
        return array_key_exists((string) $sort, self::sortOptions()) ? (string) $sort : self::SORT_NUMBER;
    }

    /**
     * Ordena mesas según el criterio elegido. El desempate siempre es el número
     * en orden natural (1, 2, 10 — no 1, 10, 2), igual en web y en la app.
     */
    public static function sortCollection($tables, ?string $sort = null)
    {
        $sort = self::normalizeSort($sort);
        $statusRank = [self::STATUS_OCUPADA => 0, self::STATUS_RESERVADA => 1, self::STATUS_LIBRE => 2, self::STATUS_CERRADA => 3];

        return $tables->sortBy(function (Table $table) use ($sort, $statusRank) {
            $natural = [$table->getNumericSortKey(), $table->number];

            return match ($sort) {
                self::SORT_STATUS => [$statusRank[$table->status] ?? 9, ...$natural],
                self::SORT_CAPACITY => [-1 * (int) $table->capacity, ...$natural],
                default => $natural,
            };
        })->values();
    }

    /**
     * Ordenar una colección de mesas por grupos numéricos (0-9, 10-19, …) y luego por número completo.
     */
    public static function sortByNumericGroup($tables)
    {
        return self::sortCollection($tables, self::SORT_NUMBER);
    }
}

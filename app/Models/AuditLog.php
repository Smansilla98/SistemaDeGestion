<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditLog extends Model
{
    public $timestamps = false;

    /**
     * Cache del chequeo de esquema para no consultar el information_schema
     * en cada escritura de auditoría.
     *
     * @var array<string, bool>
     */
    protected static array $columnCache = [];

    /**
     * Se aceptan las dos formas del esquema: la nueva (auditable_type/event/...)
     * y la histórica (action/model_type/changes/...). Si una clave no es fillable
     * Eloquent la descarta en silencio y el INSERT sale sin la columna NOT NULL
     * `action`, lo que rompía el cierre de mesas con SQLSTATE 1364.
     */
    protected $fillable = [
        'restaurant_id',
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'reason',
        'ip',
        'created_at',
        // Esquema histórico (lo sigue leyendo ModuleUsageService y lo escribe AuditService)
        'action',
        'model_type',
        'model_id',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * ¿Existe la columna en audit_logs? Resultado cacheado por request.
     */
    public static function hasAuditColumn(string $column): bool
    {
        if (array_key_exists($column, static::$columnCache)) {
            return static::$columnCache[$column];
        }

        try {
            $exists = Schema::hasColumn('audit_logs', $column);
        } catch (\Throwable $e) {
            $exists = false;
        }

        return static::$columnCache[$column] = $exists;
    }

    /**
     * Limpiar el cache de esquema (usado por checkout:verify-schema y tests).
     */
    public static function forgetSchemaCache(): void
    {
        static::$columnCache = [];
    }

    public static function record(
        Model $auditable,
        string $event,
        ?array $old = null,
        ?array $new = null,
        ?string $reason = null,
        ?int $restaurantId = null,
        ?int $userId = null,
    ): ?self {
        try {
            if (! Schema::hasTable('audit_logs')) {
                return null;
            }

            $payload = [
                'restaurant_id' => $restaurantId
                    ?? $auditable->getAttribute('restaurant_id')
                    ?? auth()->user()?->restaurant_id,
                'user_id' => $userId ?? auth()->id(),
                'created_at' => now(),
            ];

            // Forma nueva
            if (static::hasAuditColumn('auditable_type')) {
                $payload['auditable_type'] = $auditable->getMorphClass();
                $payload['auditable_id'] = $auditable->getKey();
                $payload['event'] = $event;
                $payload['old_values'] = $old;
                $payload['new_values'] = $new;
                $payload['reason'] = $reason;
                $payload['ip'] = request()?->ip();
            }

            // Forma histórica: `action` suele ser NOT NULL sin default (SQLSTATE 1364)
            // y ModuleUsageService filtra por action/model_type.
            if (static::hasAuditColumn('action')) {
                $payload['action'] = $event;
                $payload['model_type'] = $auditable->getMorphClass();
                $payload['model_id'] = $auditable->getKey();
                $payload['changes'] = $new ?? $old;

                if (static::hasAuditColumn('ip_address')) {
                    $payload['ip_address'] = request()?->ip();
                    $payload['user_agent'] = request()?->userAgent();
                }
            }

            return self::create($payload);
        } catch (\Throwable $e) {
            Log::warning('AuditLog falló', ['event' => $event, 'error' => $e->getMessage()]);

            return null;
        }
    }
}

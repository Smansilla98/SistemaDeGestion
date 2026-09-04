<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps = false;

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
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
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

    public static function record(
        Model $auditable,
        string $event,
        ?array $old = null,
        ?array $new = null,
        ?string $reason = null,
        ?int $restaurantId = null,
        ?int $userId = null,
    ): ?self {
        if (! \Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
            return null;
        }

        try {
            return self::create([
                'restaurant_id' => $restaurantId
                    ?? $auditable->getAttribute('restaurant_id')
                    ?? auth()->user()?->restaurant_id,
                'user_id' => $userId ?? auth()->id(),
                'auditable_type' => $auditable->getMorphClass(),
                'auditable_id' => $auditable->getKey(),
                'event' => $event,
                'old_values' => $old,
                'new_values' => $new,
                'reason' => $reason,
                'ip' => request()?->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AuditLog falló', ['event' => $event, 'error' => $e->getMessage()]);

            return null;
        }
    }
}

<?php

namespace App\Models;

use App\Domain\Money\Money;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransition;
use App\Models\Concerns\BelongsToRestaurant;
use App\Models\Concerns\OptimisticLocking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Order extends Model
{
    use BelongsToRestaurant;
    use HasFactory;
    use OptimisticLocking;

    const STATUS_ABIERTO = 'ABIERTO';

    const STATUS_ENVIADO = 'ENVIADO';

    const STATUS_EN_PREPARACION = 'EN_PREPARACION';

    const STATUS_LISTO = 'LISTO';

    const STATUS_ENTREGADO = 'ENTREGADO';

    const STATUS_CERRADO = 'CERRADO';

    const STATUS_CANCELADO = 'CANCELADO';

    protected $fillable = [
        'restaurant_id',
        'idempotency_key',
        'table_id',
        'subsector_item_id',
        'table_session_id',
        'user_id',
        'number',
        'status',
        'lock_version',
        'subtotal',
        'discount',
        'total',
        'subtotal_cents',
        'discount_cents',
        'total_cents',
        'observations',
        'customer_name',
        'sent_at',
        'kitchen_printed_at',
        'closed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'subtotal_cents' => 'integer',
        'discount_cents' => 'integer',
        'total_cents' => 'integer',
        'sent_at' => 'datetime',
        'kitchen_printed_at' => 'datetime',
        'closed_at' => 'datetime',
        'lock_version' => 'integer',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function subsectorItem(): BelongsTo
    {
        return $this->belongsTo(SubsectorItem::class, 'subsector_item_id');
    }

    public function tableSession(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'table_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(OrderStatusChange::class);
    }

    public static function getStatuses(): array
    {
        return OrderStatus::values();
    }

    public function statusEnum(): ?OrderStatus
    {
        return OrderStatus::tryFromLoose(is_string($this->status) ? $this->status : (string) $this->status);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ABIERTO;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CERRADO || $this->status === self::STATUS_CANCELADO;
    }

    public function calculateTotal(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('order_items', 'line_total_cents')) {
            $cents = (int) $this->items()->sum('line_total_cents');
            if ($cents > 0 || $this->items()->exists()) {
                $discountCents = \Illuminate\Support\Facades\Schema::hasColumn('orders', 'discount_cents')
                    ? (int) ($this->discount_cents ?? Money::fromDecimal($this->discount ?? 0)->cents)
                    : Money::fromDecimal($this->discount ?? 0)->cents;

                $totalCents = max(0, $cents - $discountCents);
                $payload = [
                    'subtotal' => round($cents / 100, 2),
                    'total' => round($totalCents / 100, 2),
                    'discount' => round($discountCents / 100, 2),
                ];
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'subtotal_cents')) {
                    $payload['subtotal_cents'] = $cents;
                    $payload['discount_cents'] = $discountCents;
                    $payload['total_cents'] = $totalCents;
                }
                $this->forceFill($payload)->save();

                return;
            }
        }

        $subtotal = $this->items()->sum('subtotal');
        $this->subtotal = $subtotal;
        $this->total = $subtotal - $this->discount;
        $this->save();
    }

    public function transitionTo(OrderStatus $target, ?User $actor = null, ?string $reason = null): void
    {
        $from = $this->statusEnum() ?? OrderStatus::ABIERTO;

        if ($from === $target) {
            return;
        }

        if (! $from->canTransitionTo($target)) {
            throw new InvalidOrderTransition($from, $target);
        }

        $this->update(['status' => $target->value]);
        $this->recordStatusChange($from, $target, $actor, $reason);
    }

    public function recordStatusChange(OrderStatus $from, OrderStatus $to, ?User $actor = null, ?string $reason = null): void
    {
        if (! Schema::hasTable('order_status_changes')) {
            return;
        }

        OrderStatusChange::create([
            'order_id' => $this->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'user_id' => $actor?->id ?? auth()->id(),
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}

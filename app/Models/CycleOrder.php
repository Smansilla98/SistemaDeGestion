<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CycleOrder extends Model
{
    public const LABELS = ['PENDIENTE' => 'Pendiente', 'ENTREGADO' => 'Entregado', 'CANCELADO' => 'Cancelado'];

    protected $fillable = ['order_cycle_id', 'contact_id', 'status', 'total_cents', 'paid_cents', 'delivered_at', 'note'];

    protected $casts = ['total_cents' => 'integer', 'paid_cents' => 'integer', 'delivered_at' => 'datetime'];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(OrderCycle::class, 'order_cycle_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CycleOrderItem::class);
    }
}

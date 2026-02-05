<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'product_id',
        'expected_quantity',
        'actual_quantity',
        'notes',
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'actual_quantity' => 'integer',
    ];

    /**
     * Relación: Un event_product pertenece a un evento
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relación: Un event_product pertenece a un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}


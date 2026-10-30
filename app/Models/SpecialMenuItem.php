<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialMenuItem extends Model
{
    protected $fillable = ['special_menu_id', 'product_id', 'price', 'planned_quantity'];

    protected $casts = ['price' => 'decimal:2', 'planned_quantity' => 'integer'];

    protected static function booted(): void
    {
        $forget = fn () => app(\App\Services\SpecialMenuService::class)->forget();
        static::saved($forget);
        static::deleted($forget);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(SpecialMenu::class, 'special_menu_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductModifier extends Model
{
    use HasFactory;

    /** Agregado opcional: suma (o resta) price_modifier al precio del producto. */
    const KIND_EXTRA = 'EXTRA';

    /** Variante: define el precio final del producto (ej. sabor de pizza). Se elige una sola. */
    const KIND_VARIANT = 'VARIANT';

    protected $fillable = [
        'product_id',
        'name',
        'kind',
        'price_modifier',
        'price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function isVariant(): bool
    {
        return $this->kind === self::KIND_VARIANT;
    }

    /**
     * Insumos propios de la variante (ej. jamón y morrones), además de los del producto base.
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_modifier_ingredients', 'product_modifier_id', 'ingredient_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Relación: Un modificador pertenece a un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

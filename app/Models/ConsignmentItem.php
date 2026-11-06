<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsignmentItem extends Model
{
    protected $fillable = ['consignment_id', 'market_product_id', 'unit_price_cents', 'sale_price_cents', 'qty_delivered', 'qty_sold', 'qty_returned', 'sold_cents'];

    protected $casts = ['unit_price_cents' => 'integer', 'sale_price_cents' => 'integer', 'qty_delivered' => 'float', 'qty_sold' => 'float', 'qty_returned' => 'float', 'sold_cents' => 'integer'];

    /** @return BelongsTo<Consignment, $this> */
    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    /** @return BelongsTo<MarketProduct, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketProduct::class, 'market_product_id');
    }

    public function pending(): float
    {
        return round($this->qty_delivered - $this->qty_sold - $this->qty_returned, 3);
    }

    /** Precio al que vende el espacio (si no se cargó, el mismo que el del emprendedor). */
    public function salePriceCents(): int
    {
        return $this->sale_price_cents ?? $this->unit_price_cents;
    }

    /** Lo que le corresponde al emprendedor por lo vendido, antes de la comisión porcentual. */
    public function entrepreneurCents(): int
    {
        return (int) round($this->qty_sold * $this->unit_price_cents);
    }
}

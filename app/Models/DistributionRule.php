<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;

/**
 * Regla de reparto de un taller o evento. parties = [{label, percent}] que suman 100.
 * base BRUTO reparte los ingresos; NETO reparte ingresos − gastos reconocidos.
 */
class DistributionRule extends Model
{
    use BelongsToRestaurant;

    public const BASES = ['BRUTO' => 'Ingresos brutos', 'NETO' => 'Ingresos menos gastos'];

    protected $fillable = ['restaurant_id', 'name', 'base', 'parties'];

    protected $casts = ['parties' => 'array'];

    public function summary(): string
    {
        return collect($this->parties ?? [])->map(fn ($p) => $p['label'].' '.rtrim(rtrim(number_format((float) $p['percent'], 2, ',', ''), '0'), ',').'%')->implode(' / ')
            .' sobre '.mb_strtolower(self::BASES[$this->base] ?? $this->base);
    }
}

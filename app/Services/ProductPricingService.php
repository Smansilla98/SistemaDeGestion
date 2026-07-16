<?php

namespace App\Services;

use InvalidArgumentException;

class ProductPricingService
{
    public function profitMargin(?float $cost, ?float $salePrice): ?float
    {
        if ($cost === null || $cost <= 0 || $salePrice === null) {
            return null;
        }

        return round((($salePrice - $cost) / $cost) * 100, 2);
    }

    public function salePrice(float $cost, float $profitMargin): float
    {
        if ($cost <= 0) {
            throw new InvalidArgumentException('El costo debe ser mayor a cero para calcular el precio de venta.');
        }

        return round($cost * (1 + ($profitMargin / 100)), 2);
    }

    /**
     * El margen es un dato de entrada virtual: si está presente, tiene prioridad
     * y recalcula el precio. En caso contrario se conserva el precio ingresado.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function apply(array $data, ?array $current = null): array
    {
        if (! array_key_exists('profit_margin', $data) || $data['profit_margin'] === null || $data['profit_margin'] === '') {
            unset($data['profit_margin']);

            return $data;
        }

        $cost = array_key_exists('cost_price', $data)
            ? (float) $data['cost_price']
            : (float) ($current['cost_price'] ?? 0);

        $data['price'] = $this->salePrice($cost, (float) $data['profit_margin']);
        unset($data['profit_margin']);

        return $data;
    }
}

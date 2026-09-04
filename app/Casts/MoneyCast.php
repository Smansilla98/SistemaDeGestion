<?php

namespace App\Casts;

use App\Domain\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast Eloquent: guarda enteros (centavos) y expone Money en el modelo.
 *
 * @implements CastsAttributes<Money|null, int|string|float|Money|null>
 */
final class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Money::fromCents((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Money) {
            return $value->cents;
        }

        if (is_int($value)) {
            return $value;
        }

        return Money::fromDecimal($value)->cents;
    }
}

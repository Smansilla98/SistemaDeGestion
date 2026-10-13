<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Recetas de productos: por cantidad fija o por proporción del vaso.
 *
 * Un trago de 500 cc con 30% fernet + 70% coca descuenta 150 y 350 de cada insumo.
 * La cantidad se deriva del porcentaje y se guarda, así la venta descuenta igual
 * que una receta común y no hay cuentas en la hora pico.
 */
final class RecipeService
{
    /**
     * Agrega un insumo a la receta. Con $percentage usa proporción; si no, cantidad fija.
     */
    public function addIngredient(Product $product, Product $ingredient, ?int $quantity, ?float $percentage = null): void
    {
        if ($product->type !== 'PRODUCT') {
            throw new InvalidArgumentException('Solo los productos vendibles pueden tener receta.');
        }
        if ($ingredient->type !== 'INSUMO' || (int) $ingredient->restaurant_id !== (int) $product->restaurant_id) {
            throw new InvalidArgumentException('El insumo no es válido.');
        }
        if ($product->ingredients()->where('ingredient_id', $ingredient->id)->exists()) {
            throw new InvalidArgumentException('Ese insumo ya está en la receta.');
        }

        if ($percentage !== null) {
            if (! $product->serving_size) {
                throw new InvalidArgumentException('Definí primero la medida del vaso para usar proporciones.');
            }
            if ($percentage <= 0 || $percentage > 100) {
                throw new InvalidArgumentException('El porcentaje debe estar entre 0 y 100.');
            }

            $used = (float) $product->ingredients()->sum('product_ingredients.percentage');
            if ($used + $percentage > 100.0001) {
                $free = max(0, 100 - $used);
                throw new InvalidArgumentException("Las proporciones superan el 100% del vaso. Queda libre {$free}%.");
            }

            $quantity = self::quantityFor((int) $product->serving_size, $percentage);
        }

        if ($quantity === null || $quantity < 1) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        $product->ingredients()->attach($ingredient->id, [
            'quantity' => $quantity,
            'percentage' => $percentage,
            'unit' => $ingredient->unit,
        ]);
    }

    /**
     * Cambia la medida del vaso y recalcula las cantidades de los insumos cargados por proporción.
     */
    public function setServingSize(Product $product, ?int $servingSize): void
    {
        DB::transaction(function () use ($product, $servingSize) {
            $proportional = $product->ingredients()->whereNotNull('product_ingredients.percentage')->get();

            if ($servingSize === null && $proportional->isNotEmpty()) {
                throw new InvalidArgumentException('Quitá los insumos cargados por proporción antes de borrar la medida del vaso.');
            }

            $product->update(['serving_size' => $servingSize]);

            foreach ($proportional as $ingredient) {
                $product->ingredients()->updateExistingPivot($ingredient->id, [
                    'quantity' => self::quantityFor((int) $servingSize, (float) $ingredient->getRelation('pivot')->percentage),
                ]);
            }
        });
    }

    /**
     * Cantidad de insumo para un porcentaje del vaso (mínimo 1 para no vender sin descontar).
     */
    public static function quantityFor(int $servingSize, float $percentage): int
    {
        return max(1, (int) round($servingSize * $percentage / 100));
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;

/**
 * Alta de sucursales dentro de un grupo (casa matriz + sucursales).
 */
final class BranchService
{
    /**
     * Crea una sucursal colgada de la casa matriz del restaurante de origen.
     * Opcionalmente copia la carta (categorías, productos, variantes y recetas).
     * El stock nunca se copia: la sucursal arranca en cero y se carga con una puesta a punto.
     *
     * @param  array{name:string, address?:?string, phone?:?string, email?:?string}  $data
     */
    public function createBranch(Restaurant $source, array $data, bool $copyCatalog = false): Restaurant
    {
        return DB::transaction(function () use ($source, $data, $copyCatalog) {
            $branch = Restaurant::create([
                'parent_id' => $source->groupRootId(),
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'settings' => $source->settings,
                'is_active' => true,
            ]);

            if ($copyCatalog) {
                $this->copyCatalog($source, $branch);
            }

            AuditLog::record($branch, 'branch.created', null, [
                'parent_id' => $branch->parent_id,
                'copied_catalog_from' => $copyCatalog ? $source->id : null,
            ], 'Sucursal creada', (int) $branch->id);

            return $branch;
        });
    }

    private function copyCatalog(Restaurant $source, Restaurant $branch): void
    {
        $categoryMap = [];
        $categories = Category::withoutGlobalScopes()->where('restaurant_id', $source->id)->get();
        foreach ($categories as $category) {
            $copy = $category->replicate(['sector_id']);
            $copy->restaurant_id = $branch->id;
            $copy->sector_id = null;
            $copy->save();
            $categoryMap[$category->id] = $copy->id;
        }

        $productMap = [];
        $products = Product::withoutGlobalScopes()
            ->where('restaurant_id', $source->id)
            ->with(['modifiers.ingredients', 'ingredients'])
            ->get();

        foreach ($products as $product) {
            $copy = $product->replicate(['supplier_id']);
            $copy->restaurant_id = $branch->id;
            $copy->category_id = $product->category_id ? ($categoryMap[$product->category_id] ?? null) : null;
            $copy->supplier_id = null;
            $copy->save();
            $productMap[$product->id] = $copy->id;
        }

        // Segunda pasada: recetas y variantes necesitan los ids nuevos de los insumos.
        foreach ($products as $product) {
            $copy = Product::withoutGlobalScopes()->findOrFail($productMap[$product->id]);

            foreach ($product->ingredients as $ingredient) {
                if (! isset($productMap[$ingredient->id])) {
                    continue;
                }
                $pivot = $ingredient->getRelation('pivot');
                $copy->ingredients()->attach($productMap[$ingredient->id], [
                    'quantity' => $pivot->quantity,
                    'percentage' => $pivot->percentage,
                    'unit' => $pivot->unit,
                ]);
            }

            foreach ($product->modifiers as $modifier) {
                $modifierCopy = $modifier->replicate();
                $modifierCopy->product_id = $copy->id;
                $modifierCopy->save();

                foreach ($modifier->ingredients as $ingredient) {
                    if (! isset($productMap[$ingredient->id])) {
                        continue;
                    }
                    $modifierCopy->ingredients()->attach($productMap[$ingredient->id], [
                        'quantity' => $ingredient->getRelation('pivot')->quantity,
                    ]);
                }
            }
        }
    }
}

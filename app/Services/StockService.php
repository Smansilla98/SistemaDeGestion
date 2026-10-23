<?php

namespace App\Services;

use App\Exceptions\StockInsuficienteException;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Registrar movimiento de stock
     */
    public function recordMovement(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $product = Product::findOrFail($data['product_id']);

            // Actualizar o crear stock (lock para concurrencia)
            $stock = Stock::where('restaurant_id', $data['restaurant_id'])
                ->where('product_id', $data['product_id'])
                ->lockForUpdate()
                ->first();

            $currentStock = $stock?->quantity ?? $product->getCurrentStock($data['restaurant_id']);

            // Calcular nuevo stock según el tipo
            $newStock = match ($data['type']) {
                'ENTRADA' => $currentStock + $data['quantity'],
                'SALIDA' => $currentStock - $data['quantity'],
                'AJUSTE' => $data['quantity'],
                default => throw new \Exception('Tipo de movimiento inválido'),
            };

            if ($newStock < 0) {
                throw new StockInsuficienteException("Stock insuficiente de '{$product->name}'. Disponible: {$currentStock}, necesario: {$data['quantity']}");
            }

            Stock::updateOrCreate(
                [
                    'restaurant_id' => $data['restaurant_id'],
                    'product_id' => $data['product_id'],
                ],
                ['quantity' => $newStock]
            );

            // Registrar movimiento
            $movement = StockMovement::create([
                'restaurant_id' => $data['restaurant_id'],
                'product_id' => $data['product_id'],
                'user_id' => $data['user_id'],
                'type' => $data['type'],
                'quantity' => $data['type'] === 'AJUSTE' ? abs($newStock - $currentStock) : $data['quantity'],
                'previous_stock' => $currentStock,
                'new_stock' => $newStock,
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
            ]);

            // Notificar stock bajo a ADMIN y CAJERO si quedó por debajo del mínimo
            if ($product->stock_minimum !== null && $newStock <= $product->stock_minimum) {
                $users = User::where('restaurant_id', $data['restaurant_id'])
                    ->where('is_active', true)
                    ->whereIn('role', ['ADMIN', 'CAJERO'])
                    ->get();
                foreach ($users as $user) {
                    $user->notify(new LowStockNotification($product, (int) $newStock, (int) $product->stock_minimum, $data['restaurant_id']));
                }
            }

            // Si es una ENTRADA y tiene información de compra, registrar la compra
            if ($data['type'] === 'ENTRADA' && isset($data['purchase_data'])) {
                $purchaseData = $data['purchase_data'];

                // Validar que tenga los datos requeridos
                if (! isset($purchaseData['supplier_id']) || ! isset($purchaseData['unit_cost']) || ! isset($purchaseData['purchase_date'])) {
                    throw new \Exception('Para una entrada, debe proporcionar proveedor, costo unitario y fecha de compra');
                }

                // Calcular costo total
                $totalCost = $data['quantity'] * $purchaseData['unit_cost'];

                // Crear registro de compra
                Purchase::create([
                    'stock_movement_id' => $movement->id,
                    'supplier_id' => $purchaseData['supplier_id'],
                    'purchase_date' => $purchaseData['purchase_date'],
                    'unit_cost' => $purchaseData['unit_cost'],
                    'total_cost' => $totalCost,
                    'invoice_number' => $purchaseData['invoice_number'] ?? null,
                    'notes' => $purchaseData['notes'] ?? null,
                ]);
            }

            return $movement;
        });
    }

    /**
     * Reducir la cantidad de un registro de stock puntual.
     * Devuelve el stock actualizado y evita cantidades negativas.
     */
    public function reduceStock(Stock $stock, int $quantity): Stock
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La cantidad a reducir debe ser mayor a cero');
        }

        if ($stock->quantity - $quantity < 0) {
            throw new StockInsuficienteException("Stock insuficiente. Disponible: {$stock->quantity}, a descontar: {$quantity}");
        }

        $stock->decrement('quantity', $quantity);

        return $stock->refresh();
    }

    /**
     * Indicar si un registro de stock esta por debajo (o en) su minimo.
     * Usa el minimo del propio stock y cae al minimo definido en el producto.
     */
    public function isLowStock(Stock $stock): bool
    {
        $minimum = $stock->minimum_stock ?? $stock->product?->stock_minimum ?? 0;

        return $stock->quantity <= $minimum;
    }

    /**
     * Qué se descuenta al vender una unidad-línea: receta o stock propio del producto base,
     * más los insumos de la variante/extras elegidos (ej. Pizza + jamón y morrones).
     * Si un mismo insumo aparece en ambos, se suma.
     *
     * @param  list<int>  $modifierIds  ids de product_modifiers elegidos en la línea
     * @return array<int, array{product: Product, quantity: int, unit: string, from_recipe: bool}>
     */
    private function consumptionPlan(Product $product, int $quantity, array $modifierIds = []): array
    {
        $plan = [];
        $add = function (Product $target, int $qty, string $unit, bool $fromRecipe) use (&$plan): void {
            if ($qty <= 0) {
                return;
            }
            if (isset($plan[$target->id])) {
                $plan[$target->id]['quantity'] += $qty;

                return;
            }
            $plan[$target->id] = ['product' => $target, 'quantity' => $qty, 'unit' => $unit, 'from_recipe' => $fromRecipe];
        };

        $product->loadMissing('ingredients');

        if ($product->ingredients->isNotEmpty()) {
            foreach ($product->ingredients as $ingredient) {
                $add(
                    $ingredient,
                    (int) ($ingredient->pivot->quantity * $quantity),
                    (string) ($ingredient->pivot->unit ?? $ingredient->unit ?? ''),
                    true
                );
            }
        } elseif ($product->has_stock) {
            $add($product, $quantity, '', false);
        }

        if ($modifierIds !== []) {
            $modifiers = ProductModifier::query()
                ->where('product_id', $product->id)
                ->whereIn('id', $modifierIds)
                ->with('ingredients')
                ->get();

            foreach ($modifiers as $modifier) {
                foreach ($modifier->ingredients as $ingredient) {
                    $add($ingredient, (int) ($ingredient->pivot->quantity * $quantity), (string) ($ingredient->unit ?? ''), true);
                }
            }
        }

        return $plan;
    }

    /**
     * Verificar que haya stock suficiente para vender (producto con has_stock o receta con insumos).
     * Lanza \Exception con mensaje claro si no alcanza.
     *
     * @param  list<int>  $modifierIds
     */
    public function ensureStockForSale(int $restaurantId, int $productId, int $quantity, array $modifierIds = []): void
    {
        $product = Product::with('ingredients')->findOrFail($productId);

        foreach ($this->consumptionPlan($product, $quantity, $modifierIds) as $line) {
            $target = $line['product'];
            $required = $line['quantity'];
            $current = $target->getCurrentStock($restaurantId);
            if ($current >= $required) {
                continue;
            }

            if ($line['from_recipe']) {
                $unit = $line['unit'];
                throw new StockInsuficienteException(
                    "Stock insuficiente de '{$target->name}'. Disponible: {$current} {$unit}, Necesario: {$required} {$unit}"
                );
            }

            throw new StockInsuficienteException(
                "Stock insuficiente para '{$target->name}'. Disponible: {$current}, Solicitado: {$required}"
            );
        }
    }

    /**
     * Descontar stock por venta.
     * Si el producto tiene receta (insumos), descuenta de cada insumo.
     * Si no, y tiene has_stock, descuenta del producto.
     * La variante/extras elegidos descuentan además sus propios insumos.
     *
     * @param  list<int>  $modifierIds
     */
    public function deductStockForSale(int $restaurantId, int $productId, int $quantity, ?int $orderId = null, array $modifierIds = []): void
    {
        $product = Product::with('ingredients')->findOrFail($productId);

        foreach ($this->consumptionPlan($product, $quantity, $modifierIds) as $line) {
            $this->recordMovement([
                'restaurant_id' => $restaurantId,
                'product_id' => $line['product']->id,
                'user_id' => auth()->id(),
                'type' => 'SALIDA',
                'quantity' => $line['quantity'],
                'reason' => $line['from_recipe'] ? 'Venta (receta: '.$product->name.')' : 'Venta',
                'reference' => $orderId ? "order_{$orderId}" : null,
            ]);
        }
    }

    /**
     * Reponer stock cuando se elimina/reemplaza un item de un pedido.
     * Invierte la lógica de deductStockForSale (receta vs has_stock + variante).
     *
     * @param  list<int>  $modifierIds
     */
    public function restoreStockForSale(int $restaurantId, int $productId, int $quantity, ?int $orderId = null, array $modifierIds = []): void
    {
        $product = Product::with('ingredients')->findOrFail($productId);

        foreach ($this->consumptionPlan($product, $quantity, $modifierIds) as $line) {
            $this->recordMovement([
                'restaurant_id' => $restaurantId,
                'product_id' => $line['product']->id,
                'user_id' => auth()->id(),
                'type' => 'ENTRADA',
                'quantity' => $line['quantity'],
                'reason' => 'Reversión venta (item eliminado/reemplazado)',
                'reference' => $orderId ? "order_{$orderId}" : null,
            ]);
        }
    }

    /**
     * Verificar stock mínimo y generar alertas
     */
    public function checkLowStock(int $restaurantId, bool $onlySellableProducts = false): array
    {
        $productsQuery = Product::where('restaurant_id', $restaurantId)
            ->where('has_stock', true)
            ->where('is_active', true);
        if ($onlySellableProducts) {
            $productsQuery->products();
        }
        $products = $productsQuery->get();

        $alerts = [];

        foreach ($products as $product) {
            $currentStock = $product->getCurrentStock($restaurantId);
            if ($currentStock <= $product->stock_minimum) {
                $alerts[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'current_stock' => $currentStock,
                    'minimum_stock' => $product->stock_minimum,
                ];
            }
        }

        return $alerts;
    }
}

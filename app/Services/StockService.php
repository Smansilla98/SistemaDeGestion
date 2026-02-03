<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
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
            $currentStock = $product->getCurrentStock($data['restaurant_id']);

            // Calcular nuevo stock según el tipo
            $newStock = match ($data['type']) {
                'ENTRADA' => $currentStock + $data['quantity'],
                'SALIDA' => $currentStock - $data['quantity'],
                'AJUSTE' => $data['quantity'], // En ajuste, quantity es el nuevo valor
                default => throw new \Exception('Tipo de movimiento inválido'),
            };

            if ($newStock < 0) {
                throw new \Exception('No se puede tener stock negativo');
            }

            // Actualizar o crear stock
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

            // Si es una ENTRADA y tiene información de compra, registrar la compra
            if ($data['type'] === 'ENTRADA' && isset($data['purchase_data'])) {
                $purchaseData = $data['purchase_data'];
                
                // Validar que tenga los datos requeridos
                if (!isset($purchaseData['supplier_id']) || !isset($purchaseData['unit_cost']) || !isset($purchaseData['purchase_date'])) {
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
     * Descontar stock por venta
     */
    public function deductStockForSale(int $restaurantId, int $productId, int $quantity, ?int $orderId = null): void
    {
        $product = Product::findOrFail($productId);

        if (!$product->has_stock) {
            return; // No maneja stock
        }

        $this->recordMovement([
            'restaurant_id' => $restaurantId,
            'product_id' => $productId,
            'user_id' => auth()->id(),
            'type' => 'SALIDA',
            'quantity' => $quantity,
            'reason' => 'Venta',
            'reference' => $orderId ? "order_{$orderId}" : null,
        ]);
    }

    /**
     * Verificar stock mínimo y generar alertas
     */
    public function checkLowStock(int $restaurantId): array
    {
        $products = Product::where('restaurant_id', $restaurantId)
            ->where('has_stock', true)
            ->where('is_active', true)
            ->get();

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


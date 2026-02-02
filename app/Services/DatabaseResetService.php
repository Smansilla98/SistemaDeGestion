<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseResetService
{
    /**
     * Resetear toda la base de datos excepto usuarios
     */
    public function resetDatabase(int $restaurantId): void
    {
        DB::transaction(function () use ($restaurantId) {
            // 1. Eliminar pedidos y sus items (incluyendo modificadores)
            $orderIds = DB::table('orders')
                ->where('restaurant_id', $restaurantId)
                ->pluck('id');
            
            if ($orderIds->isNotEmpty()) {
                // Obtener order_item_ids antes de eliminar
                $orderItemIds = DB::table('order_items')
                    ->whereIn('order_id', $orderIds)
                    ->pluck('id');
                
                // Eliminar modificadores de items
                if ($orderItemIds->isNotEmpty()) {
                    DB::table('order_item_modifiers')
                        ->whereIn('order_item_id', $orderItemIds)
                        ->delete();
                }
                
                // Eliminar items
                DB::table('order_items')
                    ->whereIn('order_id', $orderIds)
                    ->delete();
                
                // Eliminar pedidos
                DB::table('orders')
                    ->whereIn('id', $orderIds)
                    ->delete();
            }

            // 2. Eliminar sesiones de mesa
            DB::table('table_sessions')
                ->where('restaurant_id', $restaurantId)
                ->delete();

            // 3. Eliminar pagos
            DB::table('payments')
                ->where('restaurant_id', $restaurantId)
                ->delete();

            // 4. Eliminar sesiones de caja y movimientos
            $sessionIds = DB::table('cash_register_sessions')
                ->where('restaurant_id', $restaurantId)
                ->pluck('id');
            
            if ($sessionIds->isNotEmpty()) {
                DB::table('cash_movements')
                    ->whereIn('cash_register_session_id', $sessionIds)
                    ->delete();
                DB::table('cash_register_sessions')
                    ->whereIn('id', $sessionIds)
                    ->delete();
            }

            // 5. Eliminar movimientos de stock
            DB::table('stock_movements')
                ->where('restaurant_id', $restaurantId)
                ->delete();

            // 6. Eliminar registros de auditoría
            DB::table('audit_logs')
                ->where('restaurant_id', $restaurantId)
                ->delete();

            // 7. Resetear mesas (poner en estado LIBRE y limpiar referencias)
            DB::table('tables')
                ->where('restaurant_id', $restaurantId)
                ->update([
                    'status' => 'LIBRE',
                    'current_order_id' => null,
                    'current_session_id' => null,
                ]);

            // 8. Eliminar productos
            DB::table('products')
                ->where('restaurant_id', $restaurantId)
                ->delete();

            // 9. Eliminar categorías
            DB::table('categories')
                ->where('restaurant_id', $restaurantId)
                ->delete();

            // 10. Eliminar sectores (opcional, pero el usuario pidió resetear todo)
            DB::table('sectors')
                ->where('restaurant_id', $restaurantId)
                ->delete();

            // 11. Eliminar cajas registradoras
            DB::table('cash_registers')
                ->where('restaurant_id', $restaurantId)
                ->delete();

            // 12. Eliminar impresoras
            DB::table('printers')
                ->where('restaurant_id', $restaurantId)
                ->delete();

            // Log de la operación
            Log::info("Base de datos reseteada para restaurante ID: {$restaurantId}", [
                'user_id' => auth()->id(),
                'restaurant_id' => $restaurantId,
            ]);
        });
    }
}


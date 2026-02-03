<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notificar a mozo que un pedido está listo
     */
    public function notifyOrderReady(Order $order): void
    {
        try {
            $cacheKey = "order_ready_{$order->id}";
            $notification = [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'table_id' => $order->table_id,
                'table_number' => $order->table?->number ?? 'N/A',
                'timestamp' => now()->toIso8601String(),
            ];

            // Guardar en cache por 5 minutos
            Cache::put($cacheKey, $notification, now()->addMinutes(5));

            // También guardar en una lista de notificaciones pendientes
            $notificationsKey = "ready_orders_{$order->restaurant_id}";
            $notifications = Cache::get($notificationsKey, []);
            $notifications[] = $notification;
            
            // Mantener solo las últimas 50 notificaciones
            if (count($notifications) > 50) {
                $notifications = array_slice($notifications, -50);
            }
            
            Cache::put($notificationsKey, $notifications, now()->addMinutes(10));
        } catch (\Exception $e) {
            Log::error('Error al notificar pedido listo: ' . $e->getMessage());
        }
    }

    /**
     * Obtener notificaciones de pedidos listos para un restaurante
     */
    public function getReadyOrdersNotifications(int $restaurantId, ?int $lastNotificationId = null): array
    {
        try {
            $notificationsKey = "ready_orders_{$restaurantId}";
            $notifications = Cache::get($notificationsKey, []);

            // Filtrar por ID si se proporciona
            if ($lastNotificationId !== null) {
                $notifications = array_filter($notifications, function ($notification) use ($lastNotificationId) {
                    return $notification['order_id'] > $lastNotificationId;
                });
            }

            return array_values($notifications);
        } catch (\Exception $e) {
            Log::error('Error al obtener notificaciones: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Marcar notificación como leída
     */
    public function markAsRead(int $orderId): void
    {
        $cacheKey = "order_ready_{$orderId}";
        Cache::forget($cacheKey);
    }

    /**
     * Notificar cambio de estado de mesa
     */
    public function notifyTableStatusChanged(int $tableId, string $newStatus): void
    {
        try {
            $cacheKey = "table_status_{$tableId}";
            Cache::put($cacheKey, [
                'table_id' => $tableId,
                'status' => $newStatus,
                'timestamp' => now()->toIso8601String(),
            ], now()->addMinutes(2));
        } catch (\Exception $e) {
            Log::error('Error al notificar cambio de estado de mesa: ' . $e->getMessage());
        }
    }

    /**
     * Obtener cambios de estado de mesas
     */
    public function getTableStatusChanges(int $restaurantId, ?string $lastTimestamp = null): array
    {
        // Implementación simplificada - en producción usar Redis o WebSockets
        return [];
    }
}


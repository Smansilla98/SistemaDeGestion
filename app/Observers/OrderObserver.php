<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\AuditService;
use Illuminate\Support\Facades\Cache;

class OrderObserver
{
    protected $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Limpiar cache del dashboard cuando se crea un pedido
     */
    public function created(Order $order)
    {
        Cache::forget("dashboard_stats_{$order->restaurant_id}");
        Cache::forget("top_products_today_{$order->restaurant_id}");
    }

    /**
     * Limpiar cache del dashboard cuando se actualiza un pedido
     */
    public function updated(Order $order)
    {
        Cache::forget("dashboard_stats_{$order->restaurant_id}");
        Cache::forget("top_products_today_{$order->restaurant_id}");

        // Registrar cambio en auditoría
        if ($order->wasChanged('status')) {
            $this->auditService->log(
                'ORDER_STATUS_CHANGED',
                Order::class,
                $order->id,
                [
                    'status' => [
                        'old' => $order->getOriginal('status'),
                        'new' => $order->status,
                    ],
                    'message' => "Pedido {$order->number} cambió de {$order->getOriginal('status')} a {$order->status}"
                ]
            );
        }
    }

    /**
     * Limpiar cache cuando se elimina un pedido
     */
    public function deleted(Order $order)
    {
        Cache::forget("dashboard_stats_{$order->restaurant_id}");
        Cache::forget("top_products_today_{$order->restaurant_id}");
    }
}


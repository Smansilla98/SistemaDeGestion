<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $target = $this->order->table?->number ?? $this->order->customer_name ?? 'N/A';
        return [
            'type' => 'order_created',
            'message' => "Nuevo pedido #{$this->order->number} - {$target}",
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
            'restaurant_id' => $this->order->restaurant_id,
            'url' => route('orders.show', $this->order),
        ];
    }
}

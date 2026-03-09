<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderDispatchedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $status = 'LISTO'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_dispatched',
            'message' => "Pedido #{$this->order->number} {$this->status} - listo para entregar",
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
            'status' => $this->status,
            'restaurant_id' => $this->order->restaurant_id,
            'url' => route('orders.show', $this->order),
        ];
    }
}

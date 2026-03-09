<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product,
        public int $currentStock,
        public int $minimumStock,
        public int $restaurantId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'message' => "Stock bajo: {$this->product->name} (actual: {$this->currentStock}, mínimo: {$this->minimumStock})",
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'current_stock' => $this->currentStock,
            'minimum_stock' => $this->minimumStock,
            'restaurant_id' => $this->restaurantId,
            'url' => route('stock.index'),
        ];
    }
}

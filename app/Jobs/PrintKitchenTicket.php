<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PrintService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PrintKitchenTicket implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [5, 15, 60, 180];

    public function __construct(public int $orderId) {}

    public function uniqueId(): string
    {
        return "ticket:{$this->orderId}";
    }

    public function handle(PrintService $printService): void
    {
        $order = Order::with(['items.product', 'table', 'user'])->find($this->orderId);
        if (! $order) {
            return;
        }

        if ($order->kitchen_printed_at) {
            return;
        }

        try {
            $printer = $printService->getPrinterForKitchenTicket($order->restaurant_id);
            if ($printer) {
                $printService->printKitchenTicket($order, $printer);
            }
            $order->forceFill(['kitchen_printed_at' => now()])->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('PrintKitchenTicket falló', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

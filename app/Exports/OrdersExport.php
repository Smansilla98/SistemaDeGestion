<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected int $restaurantId,
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
        protected ?string $status = null
    ) {}

    public function query()
    {
        $query = Order::where('restaurant_id', $this->restaurantId)
            ->with(['table', 'user']);

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return ['Número', 'Mesa', 'Cliente', 'Mozo', 'Estado', 'Subtotal', 'Descuento', 'Total', 'Fecha'];
    }

    public function map($order): array
    {
        return [
            $order->number,
            $order->table?->number ?? '-',
            $order->customer_name ?? '-',
            $order->user?->name ?? '-',
            $order->status,
            $order->subtotal,
            $order->discount,
            $order->total,
            $order->created_at?->format('d/m/Y H:i'),
        ];
    }
}

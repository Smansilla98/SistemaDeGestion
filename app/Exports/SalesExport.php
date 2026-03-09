<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected string $dateFrom,
        protected string $dateTo,
        protected int $restaurantId
    ) {}

    public function query()
    {
        return Payment::where('restaurant_id', $this->restaurantId)
            ->whereBetween('created_at', [
                $this->dateFrom . ' 00:00:00',
                \Carbon\Carbon::parse($this->dateTo)->endOfDay()->format('Y-m-d H:i:s'),
            ])
            ->with(['order.table', 'order.user', 'user'])
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return ['Fecha', 'Pedido', 'Método', 'Monto', 'Operación', 'Usuario'];
    }

    public function map($payment): array
    {
        return [
            $payment->created_at?->format('d/m/Y H:i'),
            $payment->order?->number ?? '-',
            $payment->payment_method,
            $payment->amount,
            $payment->operation_number ?? '-',
            $payment->user?->name ?? '-',
        ];
    }
}

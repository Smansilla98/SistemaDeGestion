<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $restaurantId;

    public function __construct($startDate, $endDate, $restaurantId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->restaurantId = $restaurantId;
    }

    public function collection()
    {
        return Order::where('restaurant_id', $this->restaurantId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', 'CERRADO')
            ->with(['table', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Número',
            'Mesa',
            'Mozo',
            'Fecha',
            'Hora',
            'Subtotal',
            'Descuento',
            'Total',
            'Estado'
        ];
    }

    public function map($order): array
    {
        return [
            $order->number,
            $order->table->number,
            $order->user->name,
            $order->created_at->format('d/m/Y'),
            $order->created_at->format('H:i'),
            $order->subtotal,
            $order->discount,
            $order->total,
            $order->status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}


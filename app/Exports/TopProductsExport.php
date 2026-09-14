<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TopProductsExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected string $dateFrom,
        protected string $dateTo,
        protected int $restaurantId
    ) {}

    public function collection()
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.restaurant_id', $this->restaurantId)
            ->where('orders.status', 'CERRADO')
            ->whereBetween('orders.created_at', [
                $this->dateFrom.' 00:00:00',
                \Carbon\Carbon::parse($this->dateTo)->endOfDay()->format('Y-m-d H:i:s'),
            ])
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(50)
            ->get()
            ->map(fn ($r) => [
                $r->name,
                (int) $r->total_quantity,
                (float) $r->total_revenue,
            ]);
    }

    public function headings(): array
    {
        return ['Producto', 'Cantidad', 'Ingresos'];
    }
}

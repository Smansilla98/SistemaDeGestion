<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StaffExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected string $dateFrom,
        protected string $dateTo,
        protected int $restaurantId
    ) {}

    public function collection()
    {
        return DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('payments', 'orders.id', '=', 'payments.order_id')
            ->where('orders.restaurant_id', $this->restaurantId)
            ->where('orders.status', 'CERRADO')
            ->whereBetween('orders.created_at', [
                $this->dateFrom.' 00:00:00',
                \Carbon\Carbon::parse($this->dateTo)->endOfDay()->format('Y-m-d H:i:s'),
            ])
            ->select(
                'users.name',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(payments.amount) as total_sales')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn ($r) => [
                $r->name,
                (int) $r->total_orders,
                (float) $r->total_sales,
            ]);
    }

    public function headings(): array
    {
        return ['Mozo', 'Pedidos', 'Ventas'];
    }
}

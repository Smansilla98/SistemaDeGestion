<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:ADMIN,CAJERO');
    }

    /**
     * Panel de reportes
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Reporte de ventas
     */
    public function sales(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $dateFrom = $request->input('date_from', Carbon::today()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::today()->format('Y-m-d'));

        // Ventas por día
        $salesByDay = Payment::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Ventas por método de pago
        $salesByMethod = Payment::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        // Total de ventas
        $totalSales = Payment::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->sum('amount');

        // Cantidad de pedidos
        $totalOrders = Order::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->where('status', 'CERRADO')
            ->count();

        $orders = Order::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->where('status', 'CERRADO')
            ->with(['table', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('reports.sales', compact('salesByDay', 'salesByMethod', 'totalSales', 'totalOrders', 'dateFrom', 'dateTo', 'orders'));
    }

    /**
     * Exportar reporte de ventas a Excel
     */
    public function exportSales(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;
        
        $startDate = $request->input('date_from', Carbon::today()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('date_to', Carbon::today()->format('Y-m-d'));

        $filename = 'ventas_' . $startDate . '_' . $endDate . '.xlsx';

        // Nota: Requiere instalar maatwebsite/excel
        // return Excel::download(new SalesExport($startDate, $endDate, $restaurantId), $filename);
        
        return redirect()->back()->with('info', 'Funcionalidad de exportación a Excel requiere instalar el paquete maatwebsite/excel');
    }

    /**
     * Productos más vendidos
     */
    public function products(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $dateFrom = $request->input('date_from', Carbon::today()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::today()->format('Y-m-d'));

        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->where('orders.status', 'CERRADO')
            ->whereBetween('orders.created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_quantity', 'desc')
            ->limit(20)
            ->get();

        return view('reports.products', compact('topProducts', 'dateFrom', 'dateTo'));
    }

    /**
     * Ventas por mozo
     */
    public function staff(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $dateFrom = $request->input('date_from', Carbon::today()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::today()->format('Y-m-d'));

        $salesByStaff = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('payments', 'orders.id', '=', 'payments.order_id')
            ->where('orders.restaurant_id', $restaurantId)
            ->where('orders.status', 'CERRADO')
            ->whereBetween('orders.created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(payments.amount) as total_sales')
            )
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_sales', 'desc')
            ->get();

        return view('reports.staff', compact('salesByStaff', 'dateFrom', 'dateTo'));
    }
}


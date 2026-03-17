<?php

namespace App\Http\Controllers\Report;

use App\Exports\OrdersExport;
use App\Exports\ProductsExport;
use App\Exports\SalesExport;
use App\Http\Controllers\Controller;
use App\Models\CashRegisterSession;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

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

        // Sesiones de caja con actividad en el período (abiertas o cerradas en el rango)
        $cashSessions = CashRegisterSession::where('restaurant_id', $restaurantId)
            ->where('opened_at', '<=', Carbon::parse($dateTo)->endOfDay())
            ->where(function ($q) use ($dateFrom) {
                $q->whereNull('closed_at')->orWhere('closed_at', '>=', Carbon::parse($dateFrom)->startOfDay());
            })
            ->with(['cashRegister', 'user', 'payments.order.table', 'payments.order.user', 'cashMovements'])
            ->orderBy('opened_at', 'desc')
            ->get();

        return view('reports.sales', compact('salesByDay', 'salesByMethod', 'totalSales', 'totalOrders', 'dateFrom', 'dateTo', 'orders', 'cashSessions'));
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

        return Excel::download(
            new SalesExport($startDate, $endDate, $restaurantId),
            $filename
        );
    }

    /**
     * Exportar reporte de ventas a PDF
     */
    public function exportSalesPdf(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;
        $dateFrom = $request->input('date_from', Carbon::today()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::today()->format('Y-m-d'));

        $salesByDay = Payment::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        $salesByMethod = Payment::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();
        $totalSales = Payment::where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()])
            ->sum('amount');

        $pdf = Pdf::loadView('reports.sales-pdf', compact('salesByDay', 'salesByMethod', 'totalSales', 'dateFrom', 'dateTo'));
        return $pdf->download('reporte-ventas-' . $dateFrom . '-' . $dateTo . '.pdf');
    }

    /**
     * Exportar productos a Excel
     */
    public function exportProducts(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;
        $type = $request->input('type', 'PRODUCT');
        $filename = 'productos_' . date('Y-m-d') . '.xlsx';

        return Excel::download(
            new ProductsExport($restaurantId, $type),
            $filename
        );
    }

    /**
     * Exportar pedidos a Excel
     */
    public function exportOrders(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;
        $dateFrom = $request->input('date_from', Carbon::today()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::today()->format('Y-m-d'));
        $status = $request->input('status');
        $filename = 'pedidos_' . $dateFrom . '_' . $dateTo . '.xlsx';

        return Excel::download(
            new OrdersExport($restaurantId, $dateFrom, $dateTo, $status),
            $filename
        );
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


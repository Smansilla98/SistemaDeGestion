<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Table;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Mostrar dashboard principal
     */
    public function index()
    {
        $restaurantId = auth()->user()->restaurant_id;

        // Cache de estadísticas por 5 minutos
        $stats = Cache::remember("dashboard_stats_{$restaurantId}", 300, function () use ($restaurantId) {
            $today = Carbon::today();

            return [
                'today_sales' => Order::where('restaurant_id', $restaurantId)
                    ->where('status', 'CERRADO')
                    ->whereDate('created_at', $today)
                    ->sum('total'),
                
                'today_orders' => Order::where('restaurant_id', $restaurantId)
                    ->whereDate('created_at', $today)
                    ->count(),
                
                'active_orders' => Order::where('restaurant_id', $restaurantId)
                    ->whereIn('status', ['ABIERTO', 'ENVIADO', 'EN_PREPARACION', 'LISTO'])
                    ->count(),
                
                'occupied_tables' => Table::where('restaurant_id', $restaurantId)
                    ->where('status', 'OCUPADA')
                    ->count(),
                
                'total_tables' => Table::where('restaurant_id', $restaurantId)->count(),
                
                'low_stock_products' => DB::table('stocks')
                    ->join('products', 'stocks.product_id', '=', 'products.id')
                    ->where('stocks.restaurant_id', $restaurantId)
                    ->whereColumn('stocks.quantity', '<=', 'products.stock_minimum')
                    ->where('stocks.quantity', '>', 0)
                    ->count(),
            ];
        });

        // Pedidos recientes (sin cache para datos en tiempo real)
        $recentOrders = Order::where('restaurant_id', $restaurantId)
            ->with(['table', 'user'])
            ->whereIn('status', ['ABIERTO', 'ENVIADO', 'EN_PREPARACION', 'LISTO'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Top productos del día (cache 5 minutos)
        $topProducts = Cache::remember("top_products_today_{$restaurantId}", 300, function () use ($restaurantId) {
            return DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.restaurant_id', $restaurantId)
                ->where('orders.status', 'CERRADO')
                ->whereDate('orders.created_at', Carbon::today())
                ->select('products.name', DB::raw('SUM(order_items.quantity) as total_quantity'))
                ->groupBy('products.id', 'products.name')
                ->orderBy('total_quantity', 'desc')
                ->limit(5)
                ->get();
        });

        return view('dashboard', compact('stats', 'recentOrders', 'topProducts'));
    }
}

<?php

namespace App\Services;

use App\Models\CashRegisterSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    /**
     * Stats operativos compartidos entre dashboard web y mobile (mozo/cajero).
     *
     * @return array{
     *     mesas_libres: int,
     *     mesas_ocupadas: int,
     *     total_tables: int,
     *     pedidos_pendientes: int,
     *     ventas_sesion: float,
     *     tiene_sesion_abierta: bool,
     *     low_stock_products: int
     * }
     */
    public function operational(?int $restaurantId): array
    {
        $empty = [
            'mesas_libres' => 0,
            'mesas_ocupadas' => 0,
            'total_tables' => 0,
            'pedidos_pendientes' => 0,
            'ventas_sesion' => 0.0,
            'tiene_sesion_abierta' => false,
            'low_stock_products' => 0,
        ];

        if (! $restaurantId) {
            return $empty;
        }

        $occupiedTables = Table::where('restaurant_id', $restaurantId)
            ->where('status', 'OCUPADA')
            ->count();

        $totalTables = Table::where('restaurant_id', $restaurantId)->count();

        $activeSession = CashRegisterSession::where('restaurant_id', $restaurantId)
            ->where('status', CashRegisterSession::STATUS_ABIERTA)
            ->first();

        return [
            'mesas_libres' => max(0, $totalTables - $occupiedTables),
            'mesas_ocupadas' => $occupiedTables,
            'total_tables' => $totalTables,
            'pedidos_pendientes' => Order::where('restaurant_id', $restaurantId)
                ->whereIn('status', ['ABIERTO', 'ENVIADO', 'EN_PREPARACION', 'LISTO'])
                ->count(),
            'ventas_sesion' => $activeSession
                ? (float) Payment::where('cash_register_session_id', $activeSession->id)->sum('amount')
                : 0.0,
            'tiene_sesion_abierta' => (bool) $activeSession,
            'low_stock_products' => (int) DB::table('stocks')
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->where('stocks.restaurant_id', $restaurantId)
                ->whereColumn('stocks.quantity', '<=', 'products.stock_minimum')
                ->where('stocks.quantity', '>', 0)
                ->count(),
        ];
    }

    /**
     * Stats de control para ADMIN / GERENTE / SUPERADMIN en mobile.
     *
     * @return array{
     *     low_stock_products: int,
     *     stock_ok_products: int,
     *     open_cash_sessions: int,
     *     open_cash_session_labels: array<int, string>,
     *     recent_stock_movements: Collection,
     *     ventas_hoy: float,
     *     ventas_sesion: float,
     *     tiene_sesion_abierta: bool,
     *     pedidos_pendientes: int
     * }
     */
    public function management(?int $restaurantId): array
    {
        $empty = [
            'low_stock_products' => 0,
            'stock_ok_products' => 0,
            'open_cash_sessions' => 0,
            'open_cash_session_labels' => [],
            'recent_stock_movements' => collect(),
            'ventas_hoy' => 0.0,
            'ventas_sesion' => 0.0,
            'tiene_sesion_abierta' => false,
            'pedidos_pendientes' => 0,
        ];

        if (! $restaurantId) {
            return $empty;
        }

        $ops = $this->operational($restaurantId);

        $lowStock = $ops['low_stock_products'];

        $stockTracked = (int) DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.restaurant_id', $restaurantId)
            ->where('products.has_stock', true)
            ->where('products.is_active', true)
            ->count();

        $openSessions = CashRegisterSession::where('restaurant_id', $restaurantId)
            ->where('status', CashRegisterSession::STATUS_ABIERTA)
            ->with(['cashRegister', 'user'])
            ->get();

        $recentMovements = StockMovement::where('restaurant_id', $restaurantId)
            ->with(['product', 'user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Misma lógica que el dashboard web / reportes del día
        $ventasHoy = (float) Order::where('restaurant_id', $restaurantId)
            ->where('status', 'CERRADO')
            ->whereDate('created_at', Carbon::today())
            ->sum('total');

        return [
            'low_stock_products' => $lowStock,
            'stock_ok_products' => max(0, $stockTracked - $lowStock),
            'open_cash_sessions' => $openSessions->count(),
            'open_cash_session_labels' => $openSessions
                ->map(fn (CashRegisterSession $s) => $s->cashRegister->name ?? 'Caja')
                ->values()
                ->all(),
            'recent_stock_movements' => $recentMovements,
            'ventas_hoy' => $ventasHoy,
            'ventas_sesion' => $ops['ventas_sesion'],
            'tiene_sesion_abierta' => $ops['tiene_sesion_abierta'],
            'pedidos_pendientes' => $ops['pedidos_pendientes'],
        ];
    }

    /**
     * Insights del dashboard desktop para ADMIN / SUPERADMIN / GERENTE.
     *
     * @return array{
     *     recent_orders: list<array<string, mixed>>,
     *     top_products: list<array{name: string, total_quantity: int|float}>,
     *     low_stock_list: list<array{id: int, name: string, current_stock: int, stock_minimum: int}>,
     *     out_of_stock_list: list<array{id: int, name: string}>,
     *     sales_by_waiter: list<array{name: string, total_sales: float, payment_count: int}>,
     *     income_by_method: list<array{payment_method: string, total: float}>,
     *     active_tables: list<array{id: int, number: string, sector: ?string, waiter: ?string}>,
     *     today_orders: int
     * }
     */
    public function insights(?int $restaurantId): array
    {
        $empty = [
            'recent_orders' => [],
            'top_products' => [],
            'low_stock_list' => [],
            'out_of_stock_list' => [],
            'sales_by_waiter' => [],
            'income_by_method' => [],
            'active_tables' => [],
            'today_orders' => 0,
        ];

        if (! $restaurantId) {
            return $empty;
        }

        $today = Carbon::today();

        $recentOrders = Order::where('restaurant_id', $restaurantId)
            ->with(['table:id,number', 'user:id,name'])
            ->whereIn('status', ['ABIERTO', 'ENVIADO', 'EN_PREPARACION', 'LISTO'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Order $o) => [
                'id' => $o->id,
                'number' => $o->number,
                'status' => $o->status,
                'total' => (float) $o->total,
                'table' => $o->table?->number,
                'waiter' => $o->user?->name,
            ])
            ->values()
            ->all();

        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->where('orders.status', 'CERRADO')
            ->whereDate('orders.created_at', $today)
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_quantity'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'name' => (string) $r->name,
                'total_quantity' => (int) $r->total_quantity,
            ])
            ->all();

        $stockRows = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.restaurant_id', $restaurantId)
            ->where('products.has_stock', true)
            ->where('products.is_active', true)
            ->select(
                'products.id',
                'products.name',
                'products.stock_minimum',
                'stocks.quantity as current_stock'
            )
            ->get();

        $lowStockList = $stockRows
            ->filter(fn ($r) => (int) $r->current_stock > 0 && (int) $r->current_stock <= (int) $r->stock_minimum)
            ->sortBy('current_stock')
            ->take(8)
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
                'current_stock' => (int) $r->current_stock,
                'stock_minimum' => (int) $r->stock_minimum,
            ])
            ->values()
            ->all();

        $outOfStockList = $stockRows
            ->filter(fn ($r) => (int) $r->current_stock <= 0)
            ->take(8)
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
            ])
            ->values()
            ->all();

        $salesByWaiter = Payment::query()
            ->where('payments.restaurant_id', $restaurantId)
            ->whereDate('payments.created_at', $today)
            ->join('table_sessions', 'payments.table_session_id', '=', 'table_sessions.id')
            ->join('users', 'table_sessions.waiter_id', '=', 'users.id')
            ->select(
                'users.name',
                DB::raw('SUM(payments.amount) as total_sales'),
                DB::raw('COUNT(DISTINCT payments.id) as payment_count')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'name' => (string) $r->name,
                'total_sales' => (float) $r->total_sales,
                'payment_count' => (int) $r->payment_count,
            ])
            ->all();

        $incomeByMethod = Payment::query()
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'payment_method' => (string) $r->payment_method,
                'total' => (float) $r->total,
            ])
            ->all();

        $activeTables = Table::where('restaurant_id', $restaurantId)
            ->where('status', 'OCUPADA')
            ->with(['sector:id,name', 'currentSession.waiter:id,name'])
            ->orderBy('number')
            ->limit(12)
            ->get()
            ->map(fn (Table $t) => [
                'id' => $t->id,
                'number' => (string) $t->number,
                'sector' => $t->sector?->name,
                'waiter' => $t->currentSession?->waiter?->name,
            ])
            ->values()
            ->all();

        return [
            'recent_orders' => $recentOrders,
            'top_products' => $topProducts,
            'low_stock_list' => $lowStockList,
            'out_of_stock_list' => $outOfStockList,
            'sales_by_waiter' => $salesByWaiter,
            'income_by_method' => $incomeByMethod,
            'active_tables' => $activeTables,
            'today_orders' => Order::where('restaurant_id', $restaurantId)
                ->whereDate('created_at', $today)
                ->count(),
        ];
    }
}

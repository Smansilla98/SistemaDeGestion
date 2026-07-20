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
}

<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CashRegisterSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user?->role;
        $restaurantId = $user?->restaurant_id;

        $stats = [
            'mesas_libres' => 0,
            'mesas_ocupadas' => 0,
            'total_tables' => 0,
            'pedidos_pendientes' => 0,
            'ventas_sesion' => 0.0,
            'tiene_sesion_abierta' => false,
            'low_stock_products' => 0,
        ];

        if ($restaurantId) {
            $occupied = Table::where('restaurant_id', $restaurantId)->where('status', 'OCUPADA')->count();
            $total = Table::where('restaurant_id', $restaurantId)->count();

            $activeSession = CashRegisterSession::where('restaurant_id', $restaurantId)
                ->where('status', CashRegisterSession::STATUS_ABIERTA)
                ->first();

            $stats = [
                'mesas_libres' => max(0, $total - $occupied),
                'mesas_ocupadas' => $occupied,
                'total_tables' => $total,
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

        return view('mobile.dashboard', [
            'user' => $user,
            'rol' => $role,
            'stats' => $stats,
            'restaurant' => $user?->relationLoaded('restaurant')
                ? $user->restaurant
                : ($user ? \App\Models\Restaurant::find($user->restaurant_id) : null),
        ]);
    }
}

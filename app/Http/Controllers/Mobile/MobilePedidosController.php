<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\Request;

class MobilePedidosController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user?->role;
        $restaurantId = $user->restaurant_id;

        $tables = Table::where('restaurant_id', $restaurantId)
            ->orderBy('number')
            ->get();

        $products = Product::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $orders = Order::where('restaurant_id', $restaurantId)
            ->latest()
            ->limit(20)
            ->get();

        return view('mobile.pedidos.index', [
            'user' => $user,
            'rol' => $role,
            'tables' => $tables,
            'products' => $products,
            'orders' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        // Para ahora, redirigir al componente Livewire de toma de pedidos
        return redirect()->route('m.pedidos.index');
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $role = $user?->role;

        $order = Order::where('restaurant_id', $user->restaurant_id)
            ->with(['table', 'items.product'])
            ->findOrFail($id);

        return view('mobile.pedidos.show', [
            'order' => $order,
            'user' => $user,
            'rol' => $role,
        ]);
    }
}


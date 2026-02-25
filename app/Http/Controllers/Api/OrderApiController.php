<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderApiController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * Listar pedidos
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = Order::where('restaurant_id', $restaurantId)
            ->with(['table', 'user', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('table_id')) {
            $query->where('table_id', $request->table_id);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Mostrar un pedido específico
     */
    public function show(Order $order)
    {
        if ($order->restaurant_id !== auth()->user()->restaurant_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $order->load(['table', 'user', 'items.product', 'items.modifiers', 'payments']);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Datos de la comanda para impresión ESC/POS (impresora térmica USB).
     * Devuelve una estructura lista para que el frontend genere los comandos con ReceiptPrinterEncoder.
     */
    public function printComandaData(Order $order)
    {
        if ($order->restaurant_id !== auth()->user()->restaurant_id) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $order->load(['table', 'user', 'items.product', 'items.modifiers']);

        $byProduct = [];
        foreach ($order->items as $item) {
            $key = $item->product_id;
            if (!isset($byProduct[$key])) {
                $byProduct[$key] = [
                    'quantity' => 0,
                    'name' => $item->product->name,
                    'observations' => [],
                    'modifiers' => $item->modifiers->pluck('name')->implode(', '),
                ];
            }
            $byProduct[$key]['quantity'] += $item->quantity;
            if ($item->observations) {
                $byProduct[$key]['observations'][] = $item->observations;
            }
        }
        $items = array_map(function ($row) {
            return [
                'quantity' => $row['quantity'],
                'name' => $row['name'],
                'observations' => implode('; ', $row['observations']),
                'modifiers' => $row['modifiers'],
            ];
        }, array_values($byProduct));

        $comanda = [
            'order_number' => $order->number,
            'table_number' => $order->table ? (string) $order->table->number : null,
            'customer_name' => $order->customer_name ?? null,
            'waiter_name' => $order->user ? $order->user->name : '-',
            'created_at' => $order->created_at->format('d/m/Y H:i'),
            'observations' => $order->observations ?? '',
            'items' => $items,
        ];

        return response()->json([
            'success' => true,
            'data' => $comanda,
        ]);
    }

    /**
     * Crear un nuevo pedido
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'observations' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['user_id'] = auth()->id();

        $order = $this->orderService->createOrder($validated);

        foreach ($validated['items'] as $itemData) {
            $this->orderService->addItem($order, $itemData);
        }

        $order->load(['table', 'user', 'items.product']);

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Pedido creado exitosamente'
        ], 201);
    }

    /**
     * Agregar item a un pedido
     */
    public function addItem(Request $request, Order $order)
    {
        if ($order->restaurant_id !== auth()->user()->restaurant_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'observations' => 'nullable|string',
        ]);

        $this->orderService->addItem($order, $validated);

        $order->load(['items.product']);

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Item agregado exitosamente'
        ]);
    }
}


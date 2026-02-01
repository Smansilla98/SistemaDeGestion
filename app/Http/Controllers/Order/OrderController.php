<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Table;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * Mostrar lista de pedidos
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $query = Order::where('restaurant_id', $restaurantId)
            ->with(['table', 'user', 'items.product.category']);

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('table_id')) {
            $query->where('table_id', $request->table_id);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    /**
     * Mostrar formulario de creación de pedido
     */
    public function create(Request $request, ?int $tableId = null)
    {
        Gate::authorize('create', Order::class);

        $restaurantId = auth()->user()->restaurant_id;

        // Si se especifica una mesa, verificar que esté OCUPADA
        if ($tableId) {
            $selectedTable = Table::findOrFail($tableId);
            
            // Verificar que la mesa pertenezca al restaurante del usuario
            if ($selectedTable->restaurant_id !== $restaurantId) {
                abort(403, 'No tienes acceso a esta mesa');
            }
            
            // Verificar que la mesa esté OCUPADA para poder tomar pedidos
            if ($selectedTable->status !== 'OCUPADA') {
                return redirect()->route('tables.index')
                    ->with('error', 'Solo se pueden tomar pedidos en mesas ocupadas. Por favor, cambia el estado de la mesa a OCUPADA primero.');
            }
        }

        // Solo mostrar mesas OCUPADAS para seleccionar
        $tables = Table::where('restaurant_id', $restaurantId)
            ->where('status', 'OCUPADA')
            ->get();

        $products = Product::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with(['category', 'modifiers'])
            ->get()
            ->groupBy('category.name');

        $selectedTable = $tableId ? Table::find($tableId) : null;

        return view('orders.create', compact('tables', 'products', 'selectedTable'));
    }

    /**
     * Crear nuevo pedido
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Order::class);

        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'observations' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Verificar que la mesa esté OCUPADA para poder crear pedidos
        $table = Table::findOrFail($validated['table_id']);
        if ($table->status !== 'OCUPADA') {
            return back()->with('error', 'Solo se pueden tomar pedidos en mesas ocupadas. Por favor, cambia el estado de la mesa a OCUPADA primero.')
                ->withInput();
        }

        // Verificar que la mesa pertenezca al restaurante del usuario
        if ($table->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes acceso a esta mesa');
        }

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['user_id'] = auth()->id();

        try {
            $order = $this->orderService->createOrder($validated);

            // Agregar items al pedido
            foreach ($validated['items'] as $itemData) {
                try {
                    $this->orderService->addItem($order, $itemData);
                } catch (\Exception $e) {
                    // Si es un error de stock, retornar con el mensaje
                    if (str_contains($e->getMessage(), 'Stock insuficiente')) {
                        return back()
                            ->with('error', $e->getMessage())
                            ->withInput();
                    }
                    // Re-lanzar otras excepciones
                    throw $e;
                }
            }

            return redirect()->route('orders.show', $order)
                ->with('success', 'Pedido creado exitosamente');
        } catch (\Exception $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar pedido
     */
    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load(['table', 'user', 'items.product.category', 'items.modifiers', 'payments']);

        return view('orders.show', compact('order'));
    }

    /**
     * Agregar item al pedido
     */
    public function addItem(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'observations' => 'nullable|string',
            'modifiers' => 'nullable|array',
            'modifiers.*' => 'exists:product_modifiers,id',
        ]);

        try {
            $this->orderService->addItem($order, $validated);

            return back()->with('success', 'Item agregado al pedido');
        } catch (\Exception $e) {
            // Si es un error de stock, retornar con el mensaje
            if (str_contains($e->getMessage(), 'Stock insuficiente')) {
                return back()
                    ->with('error', $e->getMessage())
                    ->withInput();
            }
            // Re-lanzar otras excepciones
            throw $e;
        }
    }

    /**
     * Enviar pedido a cocina
     */
    public function sendToKitchen(Order $order)
    {
        Gate::authorize('update', $order);

        $this->orderService->sendToKitchen($order);

        return back()->with('success', 'Pedido enviado a cocina');
    }

    /**
     * Cerrar pedido y mostrar resumen
     */
    public function close(Order $order)
    {
        Gate::authorize('update', $order);

        // Cerrar el pedido
        $this->orderService->closeOrder($order);

        // Redirigir al resumen
        return redirect()->route('orders.summary', $order)
            ->with('success', 'Pedido cerrado exitosamente. Aquí está el resumen para el cliente.');
    }

    /**
     * Mostrar resumen del pedido para el cliente
     */
    public function summary(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load([
            'restaurant',
            'table',
            'user',
            'items.product.category',
            'items.modifiers',
            'payments'
        ]);

        return view('orders.summary', compact('order'));
    }

    /**
     * Cambiar estado del pedido (simplificado: solo mozo puede cambiar)
     * Flujo: ABIERTO -> EN_PREPARACION -> ENTREGADO
     */
    public function updateStatus(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $validated = $request->validate([
            'status' => 'required|in:EN_PREPARACION,ENTREGADO'
        ]);

        $newStatus = $validated['status'];
        $currentStatus = $order->status;

        // Validar transiciones permitidas
        $allowedTransitions = [
            'ABIERTO' => ['EN_PREPARACION'],
            'EN_PREPARACION' => ['ENTREGADO'],
        ];

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return back()->with('error', "No se puede cambiar el estado de {$currentStatus} a {$newStatus}");
        }

        // Actualizar estado
        $order->status = $newStatus;
        
        if ($newStatus === 'EN_PREPARACION' && !$order->sent_at) {
            $order->sent_at = now();
        }
        
        if ($newStatus === 'ENTREGADO') {
            // Opcional: marcar como listo para cerrar
        }
        
        $order->save();

        return back()->with('success', "Estado del pedido actualizado a {$newStatus}");
    }
}



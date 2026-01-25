<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\Sector;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}
    /**
     * Mostrar lista de mesas
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with(['tables' => function ($query) {
                $query->orderBy('number')
                    ->with(['currentOrder']); // Eager loading de pedido actual
            }])
            ->get();

        return view('tables.index', compact('sectors'));
    }

    /**
     * Mostrar layout visual de mesas
     */
    public function layout(Request $request, ?int $sectorId = null)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->get();

        $selectedSector = null;
        $tables = collect();

        if ($sectorId) {
            $selectedSector = Sector::where('restaurant_id', $restaurantId)
                ->where('id', $sectorId)
                ->firstOrFail();

            $tables = Table::where('sector_id', $sectorId)
                ->get();
        }

        return view('tables.layout', compact('sectors', 'selectedSector', 'tables'));
    }

    /**
     * Crear nueva mesa
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Table::class);

        $validated = $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'number' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['status'] = 'LIBRE';

        $table = Table::create($validated);

        return redirect()->route('tables.index')
            ->with('success', 'Mesa creada exitosamente');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Table $table)
    {
        Gate::authorize('update', $table);

        $restaurantId = auth()->user()->restaurant_id;
        $sectors = Sector::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->get();

        return view('tables.edit', compact('table', 'sectors'));
    }

    /**
     * Actualizar mesa
     */
    public function update(Request $request, Table $table)
    {
        Gate::authorize('update', $table);

        $validated = $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'number' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
            'status' => 'required|in:' . implode(',', Table::getStatuses()),
        ]);

        $table->update($validated);

        return redirect()->route('tables.index')
            ->with('success', 'Mesa actualizada exitosamente');
    }

    /**
     * Eliminar mesa
     */
    public function destroy(Table $table)
    {
        Gate::authorize('delete', $table);

        // Verificar que no tenga pedidos activos
        if ($table->current_order_id) {
            return back()->with('error', 'No se puede eliminar una mesa con pedido activo');
        }

        $table->delete();

        return redirect()->route('tables.index')
            ->with('success', 'Mesa eliminada exitosamente');
    }

    /**
     * Actualizar layout de mesas (posiciones)
     */
    public function updateLayout(Request $request)
    {
        Gate::authorize('update', Table::class);

        $validated = $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'tables' => 'required|array',
            'tables.*.id' => 'required|exists:tables,id',
            'tables.*.position_x' => 'required|integer|min:0',
            'tables.*.position_y' => 'required|integer|min:0',
        ]);

        foreach ($validated['tables'] as $tableData) {
            Table::where('id', $tableData['id'])
                ->where('sector_id', $validated['sector_id'])
                ->update([
                    'position_x' => $tableData['position_x'],
                    'position_y' => $tableData['position_y'],
                ]);
        }

        return response()->json(['success' => true, 'message' => 'Layout actualizado exitosamente']);
    }

    /**
     * Cerrar mesa: cierra todos los pedidos activos y libera la mesa
     */
    public function closeTable(Table $table)
    {
        Gate::authorize('update', $table);

        // Verificar que la mesa esté ocupada
        if ($table->status !== 'OCUPADA') {
            return back()->with('error', 'La mesa no está ocupada');
        }

        return DB::transaction(function () use ($table) {
            // Obtener todos los pedidos activos de la mesa (no cerrados)
            $activeOrders = Order::where('table_id', $table->id)
                ->where('status', '!=', Order::STATUS_CERRADO)
                ->where('status', '!=', Order::STATUS_CANCELADO)
                ->with(['items.product'])
                ->get();

            if ($activeOrders->isEmpty()) {
                // Si no hay pedidos activos, solo liberar la mesa
                $table->update([
                    'status' => 'LIBRE',
                    'current_order_id' => null,
                ]);

                return redirect()->route('tables.index')
                    ->with('success', 'Mesa liberada exitosamente');
            }

            // Cerrar todos los pedidos activos
            $totalAmount = 0;
            $ordersClosed = [];

            foreach ($activeOrders as $order) {
                // Cerrar el pedido usando el servicio
                $this->orderService->closeOrder($order);
                $totalAmount += $order->total;
                $ordersClosed[] = $order;
            }

            // Liberar la mesa
            $table->update([
                'status' => 'LIBRE',
                'current_order_id' => null,
            ]);

            // Redirigir a una vista de resumen con todos los pedidos cerrados
            return redirect()->route('tables.close-summary', $table)
                ->with('success', 'Mesa cerrada exitosamente')
                ->with('total_amount', $totalAmount)
                ->with('orders_closed', $ordersClosed);
        });
    }

    /**
     * Mostrar resumen de cierre de mesa
     */
    public function closeSummary(Table $table)
    {
        Gate::authorize('view', $table);

        // Obtener los pedidos que se cerraron (los más recientes de esta mesa)
        $closedOrders = Order::where('table_id', $table->id)
            ->where('status', Order::STATUS_CERRADO)
            ->whereNotNull('closed_at')
            ->with(['items.product.category', 'items.modifiers', 'user', 'payments'])
            ->orderBy('closed_at', 'desc')
            ->limit(10) // Últimos 10 pedidos cerrados
            ->get();

        $totalAmount = $closedOrders->sum('total');

        return view('tables.close-summary', compact('table', 'closedOrders', 'totalAmount'));
    }
}


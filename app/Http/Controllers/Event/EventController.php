<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Mostrar calendario de eventos
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Event::class);

        $restaurantId = auth()->user()->restaurant_id;
        
        // Obtener mes y año de la solicitud (por defecto: mes actual)
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        // Obtener eventos del mes
        $events = Event::where('restaurant_id', $restaurantId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['products', 'creator'])
            ->orderBy('date')
            ->orderBy('time')
            ->get();
        
        // Agrupar eventos por día
        $eventsByDay = [];
        foreach ($events as $event) {
            $day = $event->date->format('Y-m-d');
            if (!isset($eventsByDay[$day])) {
                $eventsByDay[$day] = [];
            }
            $eventsByDay[$day][] = $event;
        }
        
        // Calcular días del mes para el calendario
        $firstDayOfWeek = $startDate->dayOfWeek; // 0 = domingo, 6 = sábado
        $daysInMonth = $startDate->daysInMonth;
        
        // Mes anterior y siguiente
        $prevMonth = $startDate->copy()->subMonth();
        $nextMonth = $startDate->copy()->addMonth();
        
        // Verificar alertas de stock para eventos futuros
        $stockAlerts = $this->checkStockAlerts($restaurantId, $events->where('status', Event::STATUS_PROGRAMADO));
        
        return view('events.index', compact(
            'events',
            'eventsByDay',
            'year',
            'month',
            'startDate',
            'endDate',
            'firstDayOfWeek',
            'daysInMonth',
            'prevMonth',
            'nextMonth',
            'stockAlerts'
        ));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(Request $request)
    {
        Gate::authorize('create', Event::class);

        $restaurantId = auth()->user()->restaurant_id;
        
        // Obtener productos con stock y calcular stock actual
        $products = Product::where('restaurant_id', $restaurantId)
            ->where('has_stock', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function($product) use ($restaurantId) {
                $product->current_stock = $product->getCurrentStock($restaurantId);
                return $product;
            });
        
        // Fecha preseleccionada si viene por query
        $selectedDate = $request->get('date');
        
        return view('events.create', compact('products', 'selectedDate'));
    }

    /**
     * Crear nuevo evento
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Event::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'expected_attendance' => 'nullable|integer|min:0',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.expected_quantity' => 'required|integer|min:1',
            'products.*.notes' => 'nullable|string',
        ]);

        $restaurantId = auth()->user()->restaurant_id;

        // Crear evento
        $event = Event::create([
            'restaurant_id' => $restaurantId,
            'created_by' => auth()->id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'date' => $validated['date'],
            'time' => $validated['time'] ?? null,
            'expected_attendance' => $validated['expected_attendance'] ?? null,
            'status' => Event::STATUS_PROGRAMADO,
        ]);

        // Asociar productos con cantidades esperadas
        if (isset($validated['products']) && is_array($validated['products'])) {
            foreach ($validated['products'] as $productData) {
                $event->products()->attach($productData['product_id'], [
                    'expected_quantity' => $productData['expected_quantity'],
                    'notes' => $productData['notes'] ?? null,
                ]);
            }
        }

        return redirect()->route('events.index', [
            'year' => Carbon::parse($validated['date'])->year,
            'month' => Carbon::parse($validated['date'])->month
        ])
        ->with('success', 'Evento creado exitosamente');
    }

    /**
     * Mostrar evento
     */
    public function show(Event $event)
    {
        Gate::authorize('view', $event);

        $event->load(['products', 'creator', 'eventProducts.product']);
        
        $restaurantId = auth()->user()->restaurant_id;
        
        // Obtener stock actual de productos relacionados
        $productStocks = [];
        foreach ($event->products as $product) {
            $stock = Stock::where('restaurant_id', $restaurantId)
                ->where('product_id', $product->id)
                ->first();
            $productStocks[$product->id] = $stock ? $stock->quantity : 0;
        }

        return view('events.show', compact('event', 'productStocks'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Event $event)
    {
        Gate::authorize('update', $event);

        $restaurantId = auth()->user()->restaurant_id;
        
        $products = Product::where('restaurant_id', $restaurantId)
            ->where('has_stock', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function($product) use ($restaurantId) {
                $product->current_stock = $product->getCurrentStock($restaurantId);
                return $product;
            });
        
        $event->load('products');

        return view('events.edit', compact('event', 'products'));
    }

    /**
     * Actualizar evento
     */
    public function update(Request $request, Event $event)
    {
        Gate::authorize('update', $event);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'expected_attendance' => 'nullable|integer|min:0',
            'status' => 'required|in:PROGRAMADO,EN_CURSO,FINALIZADO,CANCELADO',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.expected_quantity' => 'required|integer|min:1',
            'products.*.notes' => 'nullable|string',
        ]);

        // Actualizar evento
        $event->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'date' => $validated['date'],
            'time' => $validated['time'] ?? null,
            'expected_attendance' => $validated['expected_attendance'] ?? null,
            'status' => $validated['status'],
        ]);

        // Sincronizar productos
        if (isset($validated['products']) && is_array($validated['products'])) {
            $syncData = [];
            foreach ($validated['products'] as $productData) {
                $syncData[$productData['product_id']] = [
                    'expected_quantity' => $productData['expected_quantity'],
                    'notes' => $productData['notes'] ?? null,
                ];
            }
            $event->products()->sync($syncData);
        } else {
            $event->products()->detach();
        }

        return redirect()->route('events.show', $event)
            ->with('success', 'Evento actualizado exitosamente');
    }

    /**
     * Eliminar evento
     */
    public function destroy(Event $event)
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return redirect()->route('events.index')
            ->with('success', 'Evento eliminado exitosamente');
    }

    /**
     * Verificar alertas de stock para eventos futuros
     */
    private function checkStockAlerts($restaurantId, $events)
    {
        $alerts = [];
        
        foreach ($events as $event) {
            foreach ($event->products as $product) {
                $stock = Stock::where('restaurant_id', $restaurantId)
                    ->where('product_id', $product->id)
                    ->first();
                
                $currentStock = $stock ? $stock->quantity : 0;
                $expectedQuantity = $product->pivot->expected_quantity ?? 0;
                
                if ($currentStock < $expectedQuantity) {
                    $alerts[] = [
                        'event' => $event,
                        'product' => $product,
                        'current_stock' => $currentStock,
                        'expected_quantity' => $expectedQuantity,
                        'shortage' => $expectedQuantity - $currentStock,
                    ];
                }
            }
        }
        
        return $alerts;
    }
}


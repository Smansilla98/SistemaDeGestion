<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TableController extends Controller
{
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
}


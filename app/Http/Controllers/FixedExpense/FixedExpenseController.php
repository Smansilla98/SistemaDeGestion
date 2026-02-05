<?php

namespace App\Http\Controllers\FixedExpense;

use App\Http\Controllers\Controller;
use App\Models\FixedExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class FixedExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', FixedExpense::class);

        $restaurantId = auth()->user()->restaurant_id;

        $query = FixedExpense::where('restaurant_id', $restaurantId);

        // Filtros
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $expenses = $query->orderBy('category')
            ->orderBy('name')
            ->paginate(20);

        // Calcular totales
        $totalGastos = FixedExpense::where('restaurant_id', $restaurantId)
            ->where('type', FixedExpense::TYPE_GASTO)
            ->where('is_active', true)
            ->get()
            ->sum(function ($expense) {
                return $expense->getProjectedAmountForPeriod(
                    now()->startOfMonth(),
                    now()->endOfMonth()
                );
            });

        $totalIngresos = FixedExpense::where('restaurant_id', $restaurantId)
            ->where('type', FixedExpense::TYPE_INGRESO)
            ->where('is_active', true)
            ->get()
            ->sum(function ($expense) {
                return $expense->getProjectedAmountForPeriod(
                    now()->startOfMonth(),
                    now()->endOfMonth()
                );
            });

        return view('fixed-expenses.index', compact(
            'expenses',
            'totalGastos',
            'totalIngresos'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', FixedExpense::class);

        return view('fixed-expenses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', FixedExpense::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:GASTO,INGRESO',
            'category' => 'required|in:ALQUILER,SERVICIOS,PERSONAL,OPERATIVOS,TALLER,OTROS',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:MENSUAL,QUINCENAL,SEMANAL,DIARIO,ANUAL',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['is_active'] = $request->boolean('is_active', true);

        FixedExpense::create($validated);

        return redirect()->route('fixed-expenses.index')
            ->with('success', 'Gasto/Ingreso fijo creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(FixedExpense $fixedExpense)
    {
        Gate::authorize('view', $fixedExpense);

        // Calcular proyección para los próximos 12 meses
        $projections = [];
        $currentMonth = now()->startOfMonth();
        
        for ($i = 0; $i < 12; $i++) {
            $monthStart = $currentMonth->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            
            $projections[] = [
                'month' => $monthStart->format('Y-m'),
                'month_name' => $monthStart->locale('es')->translatedFormat('F Y'),
                'amount' => $fixedExpense->getProjectedAmountForPeriod($monthStart, $monthEnd),
            ];
        }

        return view('fixed-expenses.show', compact('fixedExpense', 'projections'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FixedExpense $fixedExpense)
    {
        Gate::authorize('update', $fixedExpense);

        return view('fixed-expenses.edit', compact('fixedExpense'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FixedExpense $fixedExpense)
    {
        Gate::authorize('update', $fixedExpense);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:GASTO,INGRESO',
            'category' => 'required|in:ALQUILER,SERVICIOS,PERSONAL,OPERATIVOS,TALLER,OTROS',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:MENSUAL,QUINCENAL,SEMANAL,DIARIO,ANUAL',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $fixedExpense->update($validated);

        return redirect()->route('fixed-expenses.index')
            ->with('success', 'Gasto/Ingreso fijo actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FixedExpense $fixedExpense)
    {
        Gate::authorize('delete', $fixedExpense);

        $fixedExpense->delete();

        return redirect()->route('fixed-expenses.index')
            ->with('success', 'Gasto/Ingreso fijo eliminado exitosamente.');
    }
}


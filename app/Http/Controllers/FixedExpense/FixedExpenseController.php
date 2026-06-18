<?php

namespace App\Http\Controllers\FixedExpense;

use App\Http\Controllers\Controller;
use App\Models\FixedExpense;
use App\Services\FixedExpenseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FixedExpenseController extends Controller
{
    public function __construct(
        private FixedExpenseService $fixedExpenseService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', FixedExpense::class);

        $restaurantId = auth()->user()->restaurant_id;
        $month = $this->resolveMonth($request);

        $query = FixedExpense::where('restaurant_id', $restaurantId);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $expenses = $query->orderBy('type')
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $expenses->getCollection()->transform(function (FixedExpense $expense) use ($monthStart, $monthEnd) {
            $expense->monthly_projected = $expense->getProjectedAmountForPeriod($monthStart, $monthEnd);
            $expense->monthly_equivalent = $expense->getMonthlyEquivalent();

            return $expense;
        });

        $summary = $this->fixedExpenseService->monthlySummary($restaurantId, $month);
        $incomeBreakdown = $this->fixedExpenseService->incomeBreakdown($restaurantId, $month);
        $expenseBreakdown = $this->fixedExpenseService->expenseBreakdown($restaurantId, $month);
        $incomeByCategory = $this->fixedExpenseService->totalsByCategory($restaurantId, $month, FixedExpense::TYPE_INGRESO);
        $expenseByCategory = $this->fixedExpenseService->totalsByCategory($restaurantId, $month, FixedExpense::TYPE_GASTO);

        return view('fixed-expenses.index', compact(
            'expenses',
            'summary',
            'incomeBreakdown',
            'expenseBreakdown',
            'incomeByCategory',
            'expenseByCategory',
            'month'
        ));
    }

    public function create()
    {
        Gate::authorize('create', FixedExpense::class);

        return view('fixed-expenses.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', FixedExpense::class);

        $validated = $this->validateFixedExpense($request);
        $validated['restaurant_id'] = auth()->user()->restaurant_id;

        FixedExpense::create($validated);

        return redirect()->route('fixed-expenses.index')
            ->with('success', 'Gasto/Ingreso fijo creado exitosamente.');
    }

    public function show(FixedExpense $fixedExpense)
    {
        Gate::authorize('view', $fixedExpense);

        $projections = $this->fixedExpenseService->projectForMonths(
            $fixedExpense,
            now()->startOfMonth()
        );

        return view('fixed-expenses.show', compact('fixedExpense', 'projections'));
    }

    public function edit(FixedExpense $fixedExpense)
    {
        Gate::authorize('update', $fixedExpense);

        return view('fixed-expenses.edit', compact('fixedExpense'));
    }

    public function update(Request $request, FixedExpense $fixedExpense)
    {
        Gate::authorize('update', $fixedExpense);

        $validated = $this->validateFixedExpense($request);
        $fixedExpense->update($validated);

        return redirect()->route('fixed-expenses.index')
            ->with('success', 'Gasto/Ingreso fijo actualizado exitosamente.');
    }

    public function destroy(FixedExpense $fixedExpense)
    {
        Gate::authorize('delete', $fixedExpense);

        $fixedExpense->delete();

        return redirect()->route('fixed-expenses.index')
            ->with('success', 'Gasto/Ingreso fijo eliminado exitosamente.');
    }

    private function resolveMonth(Request $request): Carbon
    {
        if ($request->filled('month') && preg_match('/^\d{4}-\d{2}$/', $request->month)) {
            return Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        }

        return now()->startOfMonth();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFixedExpense(Request $request): array
    {
        $request->merge([
            'is_active' => $request->boolean('is_active', true),
            'due_day' => $request->filled('due_day') ? (int) $request->due_day : null,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:GASTO,INGRESO',
            'category' => 'required|in:'.implode(',', FixedExpense::allCategoryKeys()),
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:MENSUAL,QUINCENAL,SEMANAL,DIARIO,ANUAL',
            'due_day' => 'nullable|integer|min:1|max:31',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'required|boolean',
        ]);

        $allowedCategories = array_keys(FixedExpense::categoriesForType($validated['type']));
        if (! in_array($validated['category'], $allowedCategories, true)) {
            throw ValidationException::withMessages([
                'category' => 'La categoría no corresponde al tipo seleccionado.',
            ]);
        }

        return $validated;
    }
}

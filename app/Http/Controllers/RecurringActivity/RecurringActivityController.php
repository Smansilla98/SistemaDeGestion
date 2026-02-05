<?php

namespace App\Http\Controllers\RecurringActivity;

use App\Http\Controllers\Controller;
use App\Models\RecurringActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RecurringActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', RecurringActivity::class);

        $restaurantId = auth()->user()->restaurant_id;

        $query = RecurringActivity::where('restaurant_id', $restaurantId);

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $activities = $query->orderByRaw("
            CASE day_of_week
                WHEN 'MONDAY' THEN 1
                WHEN 'TUESDAY' THEN 2
                WHEN 'WEDNESDAY' THEN 3
                WHEN 'THURSDAY' THEN 4
                WHEN 'FRIDAY' THEN 5
                WHEN 'SATURDAY' THEN 6
                WHEN 'SUNDAY' THEN 7
            END
        ")
        ->orderBy('start_time')
        ->paginate(20);

        return view('recurring-activities.index', compact('activities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', RecurringActivity::class);

        return view('recurring-activities.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', RecurringActivity::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'day_of_week' => 'required|in:MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'expected_attendance' => 'nullable|integer|min:0',
            'expected_revenue' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['is_active'] = $request->boolean('is_active', true);

        RecurringActivity::create($validated);

        return redirect()->route('recurring-activities.index')
            ->with('success', 'Actividad recurrente creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RecurringActivity $recurringActivity)
    {
        Gate::authorize('view', $recurringActivity);

        // Obtener instancias para el próximo mes
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth()->addMonths(1);
        $instances = $recurringActivity->getInstancesForDateRange($startDate, $endDate);

        return view('recurring-activities.show', compact('recurringActivity', 'instances'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RecurringActivity $recurringActivity)
    {
        Gate::authorize('update', $recurringActivity);

        return view('recurring-activities.edit', compact('recurringActivity'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RecurringActivity $recurringActivity)
    {
        Gate::authorize('update', $recurringActivity);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'day_of_week' => 'required|in:MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'expected_attendance' => 'nullable|integer|min:0',
            'expected_revenue' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $recurringActivity->update($validated);

        return redirect()->route('recurring-activities.index')
            ->with('success', 'Actividad recurrente actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecurringActivity $recurringActivity)
    {
        Gate::authorize('delete', $recurringActivity);

        $recurringActivity->delete();

        return redirect()->route('recurring-activities.index')
            ->with('success', 'Actividad recurrente eliminada exitosamente.');
    }
}


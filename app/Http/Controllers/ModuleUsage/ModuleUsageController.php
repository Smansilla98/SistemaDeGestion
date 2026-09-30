<?php

namespace App\Http\Controllers\ModuleUsage;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\ModuleLicenseService;
use App\Services\ModuleUsageService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ModuleUsageController extends Controller
{
    public function __construct(
        private ModuleUsageService $moduleUsageService,
        private ModuleLicenseService $licenses,
    ) {
        $this->middleware('role:SUPERADMIN');
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $dateFrom = $request->input('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : null;
        $dateTo = $request->input('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : null;

        $restaurantId = $request->filled('restaurant_id')
            ? (int) $request->input('restaurant_id')
            : null;

        $summary = $this->moduleUsageService->getSummary($restaurantId, $dateFrom, $dateTo);
        $restaurants = Restaurant::query()->orderBy('name')->get(['id', 'name']);

        $selected = $restaurantId ? Restaurant::query()->find($restaurantId) : null;

        return view('module-usage.index', [
            'summary' => $summary,
            'restaurants' => $restaurants,
            'dateFrom' => $dateFrom?->toDateString() ?? '',
            'dateTo' => $dateTo?->toDateString() ?? '',
            'restaurantId' => $restaurantId,
            'catalog' => $this->licenses->catalog(),
            'licenseMap' => $this->licenses->mapFor($selected?->id),
            'licenseRestaurant' => $selected,
        ]);
    }

    public function updateLicenses(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'restaurant_id' => ['required', 'integer', 'exists:restaurants,id'],
            'modules' => ['nullable', 'array'],
        ]);

        $restaurant = Restaurant::query()->findOrFail((int) $validated['restaurant_id']);
        $this->licenses->save($restaurant, $validated['modules'] ?? []);

        return redirect()
            ->route('module-usage.index', ['restaurant_id' => $restaurant->id])
            ->with('success', 'Licencia de módulos actualizada para '.$restaurant->name.'.');
    }
}

<?php

namespace App\Http\Controllers\ModuleUsage;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\ModuleUsageService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ModuleUsageController extends Controller
{
    public function __construct(
        private ModuleUsageService $moduleUsageService
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

        return view('module-usage.index', [
            'summary' => $summary,
            'restaurants' => $restaurants,
            'dateFrom' => $dateFrom?->toDateString() ?? '',
            'dateTo' => $dateTo?->toDateString() ?? '',
            'restaurantId' => $restaurantId,
        ]);
    }
}

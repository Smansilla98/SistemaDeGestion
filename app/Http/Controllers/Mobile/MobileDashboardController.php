<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\DashboardStatsService;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    public function __construct(
        private DashboardStatsService $dashboardStats
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user?->role;
        $restaurantId = $user?->restaurant_id;

        $isManagement = in_array($role, ['ADMIN', 'GERENTE', 'SUPERADMIN'], true);

        $stats = $this->dashboardStats->operational($restaurantId);
        $management = $isManagement
            ? $this->dashboardStats->management($restaurantId)
            : null;

        return view('mobile.dashboard', [
            'user' => $user,
            'rol' => $role,
            'stats' => $stats,
            'management' => $management,
            'isManagement' => $isManagement,
            'restaurant' => $user?->relationLoaded('restaurant')
                ? $user->restaurant
                : ($user ? Restaurant::find($user->restaurant_id) : null),
        ]);
    }
}

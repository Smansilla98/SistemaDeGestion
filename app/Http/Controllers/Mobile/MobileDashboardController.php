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

        $isAdmin = $role === 'ADMIN';
        $isSupervisor = in_array($role, ['GERENTE', 'SUPERADMIN'], true);
        $needsManagement = $isAdmin || $isSupervisor;

        $stats = $this->dashboardStats->operational($restaurantId);
        $management = $needsManagement
            ? $this->dashboardStats->management($restaurantId)
            : null;

        return view('mobile.dashboard', [
            'user' => $user,
            'rol' => $role,
            'stats' => $stats,
            'management' => $management,
            'isAdmin' => $isAdmin,
            'isSupervisor' => $isSupervisor,
            'restaurant' => $user?->relationLoaded('restaurant')
                ? $user->restaurant
                : ($user ? Restaurant::find($user->restaurant_id) : null),
        ]);
    }
}

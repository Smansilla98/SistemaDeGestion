<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Services\DashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardOpsController extends Controller
{
    public function show(Request $request, DashboardStatsService $stats): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $role = (string) ($request->user()?->role ?? '');
        $operational = $stats->operational($restaurantId);

        $payload = [
            'role' => $role,
            'operational' => $operational,
            'management' => null,
            'insights' => null,
        ];

        if (in_array($role, ['ADMIN', 'SUPERADMIN', 'GERENTE'], true)) {
            $mgmt = $stats->management($restaurantId);
            $mgmt['recent_stock_movements'] = collect($mgmt['recent_stock_movements'] ?? [])
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'type' => $m->type,
                    'quantity' => $m->quantity,
                    'product' => $m->product?->name,
                    'user' => $m->user?->name,
                    'created_at' => optional($m->created_at)->toIso8601String(),
                ])
                ->values()
                ->all();
            $payload['management'] = $mgmt;
            $payload['insights'] = $stats->insights($restaurantId);
        }

        return ApiResponse::success($payload);
    }

    private function requireRestaurantId(Request $request): int|JsonResponse
    {
        $rid = $request->user()?->restaurant_id;
        if (! $rid) {
            return ApiResponse::error('Usuario sin restaurante asignado', 403, 'NO_RESTAURANT');
        }

        return (int) $rid;
    }
}

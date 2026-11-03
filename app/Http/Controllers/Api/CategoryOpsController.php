<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoryOpsController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $rid = $request->user()?->restaurant_id;
        if (! $rid) {
            return ApiResponse::error('Usuario sin restaurante asignado', 403, 'NO_RESTAURANT');
        }

        $rows = Category::query()
            ->where('restaurant_id', (int) $rid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return ApiResponse::success($rows->toArray());
    }
}

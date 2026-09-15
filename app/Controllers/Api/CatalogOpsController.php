<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Models\Category;
use App\Models\DiscountType;
use App\Models\Sector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD liviano de catálogo admin (categorías, sectores, descuentos).
 */
final class CatalogOpsController extends Controller
{
    public function categories(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        return ApiResponse::success(
            Category::where('restaurant_id', $rid)->orderBy('name')->get()->toArray()
        );
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'display_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'sometimes|boolean',
        ]);

        $row = Category::create([
            'restaurant_id' => $rid,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'display_order' => $data['display_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success($row->toArray(), 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = Category::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'display_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'sometimes|boolean',
        ]);
        $row->update($data);

        return ApiResponse::success($row->fresh()->toArray());
    }

    public function destroyCategory(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = Category::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }
        $row->delete();

        return ApiResponse::success(null, 200, 'Eliminado');
    }

    public function sectors(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        return ApiResponse::success(
            Sector::where('restaurant_id', $rid)->orderBy('name')->get()->toArray()
        );
    }

    public function storeSector(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $row = Sector::create([
            'restaurant_id' => $rid,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => 'SECTOR',
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success($row->toArray(), 201);
    }

    public function updateSector(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = Sector::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);
        $row->update($data);

        return ApiResponse::success($row->fresh()->toArray());
    }

    public function destroySector(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = Sector::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }
        $row->delete();

        return ApiResponse::success(null, 200, 'Eliminado');
    }

    public function discountTypes(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        return ApiResponse::success(
            DiscountType::where('restaurant_id', $rid)->orderBy('name')->get()->toArray()
        );
    }

    public function storeDiscountType(Request $request): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        $row = DiscountType::create([
            'restaurant_id' => $rid,
            'name' => $data['name'],
            'percentage' => $data['percentage'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success($row->toArray(), 201);
    }

    public function updateDiscountType(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = DiscountType::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'percentage' => 'sometimes|numeric|min:0|max:100',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);
        $row->update($data);

        return ApiResponse::success($row->fresh()->toArray());
    }

    public function destroyDiscountType(Request $request, int $id): JsonResponse
    {
        $rid = $this->rid($request);
        if ($rid instanceof JsonResponse) {
            return $rid;
        }

        $row = DiscountType::where('restaurant_id', $rid)->find($id);
        if (! $row) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }
        $row->delete();

        return ApiResponse::success(null, 200, 'Eliminado');
    }

    private function rid(Request $request): int|JsonResponse
    {
        $rid = $request->user()?->restaurant_id;
        if (! $rid) {
            return ApiResponse::error('Usuario sin restaurante asignado', 403, 'NO_RESTAURANT');
        }

        return (int) $rid;
    }
}

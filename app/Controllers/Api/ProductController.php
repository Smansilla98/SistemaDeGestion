<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\DTO\Pagination\PaginationQueryDto;
use App\Models\Product;
use App\Requests\Api\StoreProductRequest;
use App\Requests\Api\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Controlador HTTP fino: autorización, delegación al servicio y respuesta JSON unificada.
 */
final class ProductController extends Controller
{
    public function index(Request $request, ProductService $products): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $filters = $request->only(['type', 'category_id', 'is_active', 'search']);
        $pagination = PaginationQueryDto::fromRequest(
            $request->query('page') !== null ? (int) $request->query('page') : null,
            $request->query('per_page') !== null ? (int) $request->query('per_page') : null,
        );
        $paginator = $products->paginateForRestaurant($restaurantId, $filters, $pagination);

        return ApiResponse::paginated($paginator);
    }

    public function show(Request $request, int $id, ProductService $products): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        try {
            $row = $products->getById($id, $restaurantId);
        } catch (InvalidArgumentException) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        $product = Product::where('restaurant_id', $restaurantId)->find($id);
        if ($product !== null) {
            $this->authorize('view', $product);
        }

        return ApiResponse::success($row);
    }

    public function store(StoreProductRequest $request, ProductService $products): JsonResponse
    {
        $this->authorize('create', Product::class);

        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $input = $request->validated();
        if (($input['type'] ?? '') === 'INSUMO') {
            $input['price'] = $input['price'] ?? 0;
        }

        try {
            $row = $products->create($restaurantId, $input);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'CREATE_ERROR');
        }

        return ApiResponse::success($row, 201);
    }

    public function update(UpdateProductRequest $request, int $id, ProductService $products): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $product = Product::where('restaurant_id', $restaurantId)->find($id);
        if ($product === null) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        $this->authorize('update', $product);

        try {
            $row = $products->update($id, $restaurantId, $request->validated());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 404, 'NOT_FOUND');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'UPDATE_ERROR');
        }

        return ApiResponse::success($row);
    }

    public function destroy(Request $request, int $id, ProductService $products): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $product = Product::where('restaurant_id', $restaurantId)->find($id);
        if ($product === null) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        $this->authorize('delete', $product);

        try {
            $products->delete($id, $restaurantId);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 409, 'CONFLICT');
        }

        return ApiResponse::success(['deleted' => true], 200, 'Eliminado');
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

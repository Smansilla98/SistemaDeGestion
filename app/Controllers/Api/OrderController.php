<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\DTO\Pagination\PaginationQueryDto;
use App\Models\Order;
use App\Requests\Api\StoreOrderRequest;
use App\Requests\Api\UpdateOrderRequest;
use App\Services\OrderRestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * API REST de pedidos: creación vía OrderService; lectura híbrida; mutaciones con Eloquent.
 */
final class OrderController extends Controller
{
    public function index(Request $request, OrderRestService $orders): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $filters = $request->only(['status', 'table_id']);
        $pagination = PaginationQueryDto::fromRequest(
            $request->query('page') !== null ? (int) $request->query('page') : null,
            $request->query('per_page') !== null ? (int) $request->query('per_page') : null,
        );
        $paginator = $orders->paginateForRestaurant($restaurantId, $filters, $pagination);

        return ApiResponse::paginated($paginator);
    }

    public function show(Request $request, int $id, OrderRestService $orders): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        try {
            $payload = $orders->getById($id, $restaurantId);
        } catch (InvalidArgumentException) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        $order = Order::where('restaurant_id', $restaurantId)->find($id);
        if ($order !== null) {
            $this->authorize('view', $order);
        }

        return ApiResponse::success($payload);
    }

    public function store(StoreOrderRequest $request, OrderRestService $orders): JsonResponse
    {
        $this->authorize('create', Order::class);

        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        try {
            $order = $orders->create($restaurantId, (int) $request->user()->id, $request->validated());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'CREATE_ERROR');
        }

        return ApiResponse::success($order->toArray(), 201);
    }

    public function update(UpdateOrderRequest $request, int $id, OrderRestService $orders): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $order = Order::where('restaurant_id', $restaurantId)->find($id);
        if ($order === null) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        Gate::authorize('update', $order);

        try {
            $fresh = $orders->update($id, $restaurantId, $request->validated());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 404, 'NOT_FOUND');
        }

        return ApiResponse::success($fresh->toArray());
    }

    public function destroy(Request $request, int $id, OrderRestService $orders): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $order = Order::where('restaurant_id', $restaurantId)->find($id);
        if ($order === null) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        Gate::authorize('delete', $order);

        try {
            $orders->delete($id, $restaurantId);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422, 'DELETE_ERROR');
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

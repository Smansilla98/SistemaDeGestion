<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\DTO\Pagination\PaginationQueryDto;
use App\Requests\Api\StoreUserRequest;
use App\Requests\Api\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * CRUD de usuarios del restaurante (JWT + rol ADMIN/GERENTE + permisos RBAC).
 */
final class UserController extends Controller
{
    public function index(Request $request, UserService $users): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        $pagination = PaginationQueryDto::fromRequest(
            $request->query('page') !== null ? (int) $request->query('page') : null,
            $request->query('per_page') !== null ? (int) $request->query('per_page') : null,
        );

        return ApiResponse::paginated($users->paginateForRestaurant($restaurantId, $pagination));
    }

    public function show(Request $request, int $id, UserService $users): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        try {
            $row = $users->getById($id, $restaurantId);
        } catch (InvalidArgumentException) {
            return ApiResponse::error('No encontrado', 404, 'NOT_FOUND');
        }

        return ApiResponse::success($row);
    }

    public function store(StoreUserRequest $request, UserService $users): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        try {
            $row = $users->create($restaurantId, $request->validated());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'CREATE_ERROR');
        }

        return ApiResponse::success($row, 201);
    }

    public function update(UpdateUserRequest $request, int $id, UserService $users): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        try {
            $row = $users->update($id, $restaurantId, $request->validated());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 404, 'NOT_FOUND');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422, 'UPDATE_ERROR');
        }

        return ApiResponse::success($row);
    }

    public function destroy(Request $request, int $id, UserService $users): JsonResponse
    {
        $restaurantId = $this->requireRestaurantId($request);
        if ($restaurantId instanceof JsonResponse) {
            return $restaurantId;
        }

        try {
            $users->delete($id, $restaurantId, (int) $request->user()->id);
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

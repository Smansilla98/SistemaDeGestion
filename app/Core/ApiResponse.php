<?php

declare(strict_types=1);

namespace App\Core;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Respuestas JSON homogéneas para la API (portfolio / clientes móviles).
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>|object|null  $data
     */
    public static function success(mixed $data = null, int $status = 200, ?string $message = null): JsonResponse
    {
        $body = [
            'success' => true,
            'data' => $data,
        ];
        if ($message !== null) {
            $body['message'] = $message;
        }

        return response()->json($body, $status);
    }

    /**
     * @param  array<int, mixed>|null  $extra  metadatos adicionales junto a meta
     */
    public static function paginated(LengthAwarePaginator $paginator, ?string $message = null, ?array $extra = null): JsonResponse
    {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
        if ($extra !== null) {
            $meta = array_merge($meta, $extra);
        }

        $body = [
            'success' => true,
            'data' => $paginator->items(),
            'meta' => $meta,
        ];
        if ($message !== null) {
            $body['message'] = $message;
        }

        return response()->json($body, 200);
    }

    /**
     * @param  array<string, array<int, string>>|null  $errors
     */
    public static function error(string $message, int $status = 400, ?string $code = null, ?array $errors = null): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];
        if ($code !== null) {
            $body['code'] = $code;
        }
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}

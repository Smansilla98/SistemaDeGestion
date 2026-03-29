<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\DTO\Auth\LoginCredentialsDto;
use App\DTO\Auth\RegisterUserDto;
use App\Exceptions\ApiException;
use App\Services\JwtAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Autenticación stateless con JWT (portfolio / SPA / mobile).
 */
final class AuthJwtController extends Controller
{
    public function login(Request $request, JwtAuthService $auth): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        try {
            $payload = $auth->login(LoginCredentialsDto::fromArray($validated));
        } catch (ApiException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getErrorCode(),
                $e->getErrors()
            );
        }

        return ApiResponse::success($payload->toArray());
    }

    public function register(Request $request, JwtAuthService $auth): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $payload = $auth->register(RegisterUserDto::fromArray($validated));
        } catch (ApiException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getErrorCode(),
                $e->getErrors()
            );
        }

        return ApiResponse::success($payload->toArray(), 201);
    }

    public function me(Request $request, JwtAuthService $auth): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return ApiResponse::error('No autenticado', 401, 'UNAUTHENTICATED');
        }

        return ApiResponse::success($auth->me($user));
    }
}

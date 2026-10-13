<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ApiResponse;
use App\Core\JwtTokenService;
use App\Models\User;
use App\Support\BranchAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida cabecera Authorization: Bearer {token} y autentica al usuario para la petición actual (sin sesión).
 */
final class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return ApiResponse::error('Token no proporcionado', 401, 'MISSING_TOKEN');
        }

        $raw = trim(substr($header, 7));
        if ($raw === '') {
            return ApiResponse::error('Token no proporcionado', 401, 'MISSING_TOKEN');
        }

        try {
            $claims = app(JwtTokenService::class)->decode($raw);
        } catch (\Throwable) {
            return ApiResponse::error('Token inválido o expirado', 401, 'INVALID_TOKEN');
        }

        $userId = (int) ($claims->sub ?? 0);
        $user = User::query()->find($userId);
        if ($user === null || ! $user->is_active) {
            return ApiResponse::error('Usuario no válido', 401, 'USER_INVALID');
        }

        Auth::onceUsingId($user->id);

        // Sucursal elegida en la app: viaja en el token y se revalida en cada request.
        $authUser = Auth::user();
        $branchId = (int) ($claims->restaurant_id ?? 0);
        $branchSelected = ! empty($claims->branch_selected) && $branchId > 0
            && $authUser instanceof User
            && app(BranchAccess::class)->canAccess($authUser, $branchId);

        if ($branchSelected) {
            $authUser->useRestaurant($branchId);
        }
        $request->attributes->set('branch_selected', $branchSelected);

        return $next($request);
    }
}

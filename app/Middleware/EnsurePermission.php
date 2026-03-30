<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ApiResponse;
use App\Core\Rbac\RbacChecker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RBAC basado en config/rbac.php (permisos simbólicos por rol).
 */
final class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        if ($user === null) {
            return ApiResponse::error('No autenticado', 401, 'UNAUTHENTICATED');
        }

        if (! RbacChecker::roleHasAny($user->role, $permissions)) {
            return ApiResponse::error('No tenés permiso para esta acción', 403, 'FORBIDDEN');
        }

        return $next($request);
    }
}

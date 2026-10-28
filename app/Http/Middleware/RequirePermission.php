<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web: exige un permiso de config/permissions.php (rol de la organización activa + overrides).
 * Uso: ->middleware('perm:workshops.view').
 */
class RequirePermission
{
    public function __construct(private PermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $this->permissions->allowed($request->user(), $permission)) {
            abort(403, 'No tenés permiso para esta sección.');
        }

        return $next($request);
    }
}

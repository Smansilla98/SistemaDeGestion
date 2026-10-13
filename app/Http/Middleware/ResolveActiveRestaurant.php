<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\BranchAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web: aplica la sucursal elegida en la sesión. Si el usuario puede operar
 * más de una y todavía no eligió, lo manda a elegir antes de seguir.
 */
class ResolveActiveRestaurant
{
    public const SESSION_KEY = 'active_restaurant_id';

    public function __construct(private BranchAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $request->hasSession()) {
            return $next($request);
        }

        $branches = $this->access->branchesFor($user);
        if ($branches->count() <= 1) {
            return $next($request);
        }

        $activeId = (int) $request->session()->get(self::SESSION_KEY, 0);
        $active = $activeId > 0 ? $branches->firstWhere('id', $activeId) : null;

        if ($active !== null) {
            $user->useRestaurant($activeId);
            View::share('branchSwitcher', ['current' => $active, 'branches' => $branches]);

            return $next($request);
        }

        $request->session()->forget(self::SESSION_KEY);

        if ($request->routeIs('branch.select', 'branch.store', 'logout')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Elegí una sucursal para continuar.',
                'code' => 'BRANCH_REQUIRED',
            ], 409);
        }

        return redirect()->route('branch.select');
    }
}

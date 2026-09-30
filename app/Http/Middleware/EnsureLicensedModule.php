<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\ApiResponse;
use App\Services\ModuleLicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea web y API cuando el módulo no está licenciado para el restaurante.
 */
class EnsureLicensedModule
{
    public function __construct(
        private readonly ModuleLicenseService $licenses,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        $key = $request->is('api/*')
            ? $this->licenses->moduleForApiPath($request->path())
            : $this->licenses->moduleForWebRoute($request->route()?->getName());

        if ($key === null || $this->licenses->enabledForUser($user, $key)) {
            return $next($request);
        }

        $label = $this->licenses->catalog()[$key]['label'] ?? $key;
        $message = "El módulo {$label} no está habilitado en este local.";

        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::error($message, 403, 'MODULE_DISABLED');
        }

        return redirect()
            ->route('dashboard')
            ->with('error', $message);
    }
}

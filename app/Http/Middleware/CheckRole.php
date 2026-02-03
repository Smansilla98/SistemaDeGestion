<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Mejora: Validación robusta de roles y restaurante
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'No autenticado'], 401);
            }
            return redirect()->route('login')->with('error', 'Debes iniciar sesión para acceder a esta sección');
        }

        $user = auth()->user();

        // Validar que el usuario esté activo
        if (!$user->is_active) {
            auth()->logout();
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'Tu cuenta está desactivada'], 403);
            }
            return redirect()->route('login')->with('error', 'Tu cuenta está desactivada');
        }

        // Validar rol
        if (!in_array($user->role, $roles)) {
            Log::warning('Intento de acceso no autorizado', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'required_roles' => $roles,
                'route' => $request->route()->getName(),
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'No tienes permiso para acceder a esta sección'], 403);
            }
            abort(403, 'No tienes permiso para acceder a esta sección');
        }

        // Validar restaurante si aplica
        if ($user->restaurant_id && $request->route()->hasParameter('restaurant')) {
            $restaurantId = $request->route()->parameter('restaurant');
            if ($restaurantId && $restaurantId != $user->restaurant_id) {
                Log::warning('Intento de acceso a restaurante no autorizado', [
                    'user_id' => $user->id,
                    'user_restaurant_id' => $user->restaurant_id,
                    'requested_restaurant_id' => $restaurantId,
                    'ip' => $request->ip(),
                ]);

                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json(['message' => 'No tienes acceso a este restaurante'], 403);
                }
                abort(403, 'No tienes acceso a este restaurante');
            }
        }

        return $next($request);
    }
}


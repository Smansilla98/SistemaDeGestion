<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CheckSessionTimeout
{
    /**
     * Handle an incoming request.
     * Verifica si la sesión ha expirado por inactividad
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = Session::get('last_activity');
            $timeout = config('session.lifetime', 120) * 60; // Convertir minutos a segundos

            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                // Sesión expirada por inactividad
                Auth::logout();
                Session::invalidate();
                Session::regenerateToken();

                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'message' => 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.',
                        'session_expired' => true
                    ], 401);
                }

                return redirect()->route('login')
                    ->with('error', 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.');
            }

            // Actualizar última actividad
            Session::put('last_activity', time());
        }

        return $next($request);
    }
}


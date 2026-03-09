<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectMobile
{
    /**
     * Detectar dispositivos móviles y redirigir al dashboard mobile.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar a usuarios autenticados y a la ruta dashboard
        if ($request->user() && $request->routeIs('dashboard') && $this->isMobile($request)) {
            return redirect()->route('m.dashboard');
        }

        return $next($request);
    }

    protected function isMobile(Request $request): bool
    {
        $agent = strtolower($request->header('User-Agent', ''));
        if ($agent === '') {
            return false;
        }

        $mobiles = [
            'iphone', 'ipod', 'ipad', 'android', 'blackberry', 'opera mini',
            'windows phone', 'windows mobile', 'palm', 'symbian', 'mobile',
        ];

        foreach ($mobiles as $needle) {
            if (str_contains($agent, $needle)) {
                return true;
            }
        }

        return false;
    }
}


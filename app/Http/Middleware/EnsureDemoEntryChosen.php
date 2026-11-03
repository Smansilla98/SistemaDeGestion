<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\DemoEntry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En main, admin y superadmin eligen demo o módulos antes de entrar al sistema.
 */
class EnsureDemoEntryChosen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! DemoEntry::decides($user) || $request->routeIs('entry.*')) {
            return $next($request);
        }

        if (DemoEntry::mode() === null) {
            return redirect()->route('entry.choose');
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

use Illuminate\Support\Facades\Event;

/**
 * Fachada mínima para eventos de dominio internos (sobre Illuminate\Support\Facades\Event).
 * Centraliza el nombre del bus y facilita test doubles.
 */
final class InternalEvents
{
    /**
     * @param  object  $event  instancia de evento (p.ej. UserSignedInViaJwt)
     */
    public static function dispatch(object $event): void
    {
        Event::dispatch($event);
    }
}

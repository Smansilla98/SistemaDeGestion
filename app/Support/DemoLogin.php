<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\ApiException;

/**
 * Credenciales de demostración: mismo mensaje en login web y API JWT.
 */
final class DemoLogin
{
    public static function enabled(): bool
    {
        return (bool) config('demo_login.enabled', true);
    }

    public static function message(): string
    {
        return (string) config('demo_login.message', 'hola, gracias por probar la app :)');
    }

    public static function matches(string $username, string $password): bool
    {
        if (! self::enabled()) {
            return false;
        }

        $expectedUser = (string) config('demo_login.username', 'demo');
        $expectedPass = (string) config('demo_login.password', 'demo1234');

        if ($expectedUser === '' || $expectedPass === '') {
            return false;
        }

        return strcasecmp(trim($username), trim($expectedUser)) === 0
            && hash_equals($expectedPass, $password);
    }

    /**
     * Si coincide, lanza ApiException con el mensaje amable (API JWT).
     */
    public static function abortIfMatched(string $username, string $password): void
    {
        if (self::matches($username, $password)) {
            throw new ApiException(self::message(), 403, 'DEMO_THANKS');
        }
    }
}

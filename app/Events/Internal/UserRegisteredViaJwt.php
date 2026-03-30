<?php

declare(strict_types=1);

namespace App\Events\Internal;

/**
 * Evento de dominio: alta de usuario desde la API JWT.
 */
final class UserRegisteredViaJwt
{
    public function __construct(
        public readonly int $userId,
        public readonly string $username
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Events\Internal;

/**
 * Evento de dominio: inicio de sesión correcto vía JWT (auditoría / métricas).
 */
final class UserSignedInViaJwt
{
    public function __construct(
        public readonly int $userId,
        public readonly string $username
    ) {}
}

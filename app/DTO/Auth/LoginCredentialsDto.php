<?php

declare(strict_types=1);

namespace App\DTO\Auth;

/**
 * Credenciales de login API (usuario + contraseña).
 */
final readonly class LoginCredentialsDto
{
    public function __construct(
        public string $username,
        public string $password
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            username: trim((string) ($input['username'] ?? '')),
            password: (string) ($input['password'] ?? '')
        );
    }
}

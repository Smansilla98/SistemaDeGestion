<?php

declare(strict_types=1);

namespace App\DTO\Auth;

/**
 * Datos de registro vía API (mismo restaurante activo por defecto que el flujo web).
 */
final readonly class RegisterUserDto
{
    public function __construct(
        public string $name,
        public string $username,
        public string $password,
        public string $passwordConfirmation
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            name: trim((string) ($input['name'] ?? '')),
            username: trim((string) ($input['username'] ?? '')),
            password: (string) ($input['password'] ?? ''),
            passwordConfirmation: (string) ($input['password_confirmation'] ?? '')
        );
    }
}

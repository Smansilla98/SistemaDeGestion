<?php

declare(strict_types=1);

namespace App\DTO\Auth;

/**
 * Respuesta estándar de login/register con JWT.
 *
 * @phpstan-type TUser array{id:int,name:string,username:string,role:string,restaurant_id:?int,is_active:bool}
 */
final readonly class AuthTokenPayloadDto
{
    /**
     * @param  array<string, mixed>  $user  resumen público del usuario
     */
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
        public array $user
    ) {}

    /**
     * @return array{access_token:string,token_type:string,expires_in:int,user:array<string,mixed>}
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'user' => $this->user,
        ];
    }
}

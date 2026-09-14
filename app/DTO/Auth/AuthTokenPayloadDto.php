<?php

declare(strict_types=1);

namespace App\DTO\Auth;

/**
 * Respuesta estándar de login/register/refresh con JWT + refresh opaco.
 *
 * @phpstan-type TUser array{id:int,name:string,username:string,role:string,restaurant_id:?int,is_active:bool}
 */
final readonly class AuthTokenPayloadDto
{
    /**
     * @param  array<string, mixed>  $user
     */
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
        public array $user,
        public ?string $refreshToken = null,
        public ?int $refreshExpiresIn = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'user' => $this->user,
        ];

        if ($this->refreshToken !== null) {
            $data['refresh_token'] = $this->refreshToken;
            $data['refresh_expires_in'] = $this->refreshExpiresIn;
        }

        return $data;
    }
}

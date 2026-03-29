<?php

declare(strict_types=1);

namespace App\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;
use stdClass;
use UnexpectedValueException;

/**
 * Emisión y validación de JWT (HS256) con firebase/php-jwt.
 */
final class JwtTokenService
{
    private string $secret;

    private int $ttl;

    private string $issuer;

    private string $algo;

    public function __construct()
    {
        $secret = config('jwt.secret') ?: config('app.key');
        if (! is_string($secret) || $secret === '') {
            throw new InvalidArgumentException('JWT: falta jwt.secret o APP_KEY para firmar tokens.');
        }
        $this->secret = $this->normalizeKey($secret);
        $this->ttl = (int) config('jwt.ttl', 3600);
        $this->issuer = (string) config('jwt.issuer', config('app.url'));
        $this->algo = (string) config('jwt.algo', 'HS256');
    }

    /**
     * Genera un access token con claims mínimos para RBAC en API.
     *
     * @return array{token: string, expires_in: int}
     */
    public function issueForUser(Authenticatable $user): array
    {
        $now = time();
        $exp = $now + $this->ttl;

        $payload = [
            'iss' => $this->issuer,
            'sub' => (string) $user->getAuthIdentifier(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'role' => $user->role ?? null,
            'restaurant_id' => $user->restaurant_id ?? null,
        ];

        $token = JWT::encode($payload, $this->secret, $this->algo);

        return ['token' => $token, 'expires_in' => $this->ttl];
    }

    /**
     * Decodifica y valida firma + exp. Devuelve el objeto de claims.
     *
     * @throws UnexpectedValueException
     */
    public function decode(string $jwt): stdClass
    {
        return JWT::decode($jwt, new Key($this->secret, $this->algo));
    }

    private function normalizeKey(string $key): string
    {
        if (str_starts_with($key, 'base64:')) {
            return (string) base64_decode(substr($key, 7), true);
        }

        return $key;
    }
}

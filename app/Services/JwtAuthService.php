<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\InternalEvents;
use App\Core\JwtTokenService;
use App\DTO\Auth\AuthTokenPayloadDto;
use App\DTO\Auth\LoginCredentialsDto;
use App\DTO\Auth\RegisterUserDto;
use App\Events\Internal\UserRegisteredViaJwt;
use App\Events\Internal\UserSignedInViaJwt;
use App\Exceptions\ApiException;
use App\Models\RefreshToken;
use App\Models\Restaurant;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Casos de uso de autenticación JWT (login, registro, refresh, logout, perfil).
 */
final class JwtAuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly JwtTokenService $jwt
    ) {}

    public function login(LoginCredentialsDto $dto, ?Request $request = null): AuthTokenPayloadDto
    {
        if ($dto->username === '' || $dto->password === '') {
            throw new ApiException('Usuario y contraseña son obligatorios.', 422, 'VALIDATION_ERROR');
        }

        $row = $this->users->findByUsernameWithPassword($dto->username);
        if ($row === null || ! password_verify($dto->password, (string) $row['password'])) {
            throw new ApiException('Credenciales inválidas.', 401, 'INVALID_CREDENTIALS');
        }

        if (empty($row['is_active'])) {
            throw new ApiException('Cuenta desactivada.', 403, 'ACCOUNT_DISABLED');
        }

        $user = User::query()->find((int) $row['id']);
        if ($user === null) {
            throw new ApiException('Usuario no encontrado.', 404, 'USER_NOT_FOUND');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        InternalEvents::dispatch(new UserSignedInViaJwt(userId: $user->id, username: $user->username));

        return $this->buildTokenResponse($user, $request);
    }

    public function register(RegisterUserDto $dto, ?Request $request = null): AuthTokenPayloadDto
    {
        if ($dto->name === '' || $dto->username === '') {
            throw new ApiException('Nombre y usuario son obligatorios.', 422, 'VALIDATION_ERROR');
        }

        if (strlen($dto->password) < 8) {
            throw new ApiException('La contraseña debe tener al menos 8 caracteres.', 422, 'VALIDATION_ERROR');
        }

        if ($dto->password !== $dto->passwordConfirmation) {
            throw new ApiException('La confirmación de contraseña no coincide.', 422, 'VALIDATION_ERROR');
        }

        $exists = DB::table('users')->where('username', $dto->username)->exists();
        if ($exists) {
            throw new ApiException('El nombre de usuario ya está en uso.', 422, 'USERNAME_TAKEN');
        }

        $restaurant = Restaurant::query()->where('is_active', true)->orderBy('id')->first();
        if ($restaurant === null) {
            throw new ApiException('No hay restaurantes activos para asignar la cuenta.', 503, 'NO_RESTAURANT');
        }

        $hash = password_hash($dto->password, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new ApiException('No se pudo procesar la contraseña.', 500, 'HASH_ERROR');
        }

        $created = $this->users->create([
            'restaurant_id' => $restaurant->id,
            'name' => $dto->name,
            'username' => $dto->username,
            'email' => $dto->username.'@restaurant.portfolio',
            'password' => $hash,
            'role' => User::ROLE_MOZO,
            'is_active' => true,
        ]);

        if ($created === null) {
            throw new ApiException('No se pudo crear el usuario.', 500, 'CREATE_FAILED');
        }

        $user = User::query()->findOrFail((int) $created['id']);

        InternalEvents::dispatch(new UserRegisteredViaJwt(userId: $user->id, username: $user->username));

        return $this->buildTokenResponse($user, $request);
    }

    public function refresh(string $plainRefreshToken, ?Request $request = null): AuthTokenPayloadDto
    {
        $hash = hash('sha256', $plainRefreshToken);
        $row = RefreshToken::query()->where('token_hash', $hash)->first();

        if ($row === null || ! $row->isValid()) {
            throw new ApiException('Refresh token inválido o expirado.', 401, 'INVALID_REFRESH');
        }

        $user = User::query()->find($row->user_id);
        if ($user === null || ! $user->is_active) {
            $row->forceFill(['revoked_at' => now()])->save();
            throw new ApiException('Cuenta no disponible.', 403, 'ACCOUNT_DISABLED');
        }

        $row->forceFill(['revoked_at' => now()])->save();

        return $this->buildTokenResponse($user, $request);
    }

    public function logout(?string $plainRefreshToken): void
    {
        if ($plainRefreshToken === null || $plainRefreshToken === '') {
            return;
        }

        $hash = hash('sha256', $plainRefreshToken);
        RefreshToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function me(User $user): array
    {
        return $this->userToPublicArray($user);
    }

    private function buildTokenResponse(User $user, ?Request $request = null): AuthTokenPayloadDto
    {
        $issued = $this->jwt->issueForUser($user);
        [$plainRefresh, $refreshTtl] = $this->issueRefreshToken($user, $request);

        return new AuthTokenPayloadDto(
            accessToken: $issued['token'],
            tokenType: 'Bearer',
            expiresIn: $issued['expires_in'],
            user: $this->userToPublicArray($user),
            refreshToken: $plainRefresh,
            refreshExpiresIn: $refreshTtl,
        );
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function issueRefreshToken(User $user, ?Request $request = null): array
    {
        $ttl = (int) config('jwt.refresh_ttl', 60 * 60 * 24 * 30);
        $plain = Str::random(64);

        RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addSeconds($ttl),
            'device_name' => $request?->header('X-Device-Name'),
            'ip' => $request?->ip(),
        ]);

        return [$plain, $ttl];
    }

    /**
     * @return array<string, mixed>
     */
    private function userToPublicArray(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'restaurant_id' => $user->restaurant_id,
            'is_active' => (bool) $user->is_active,
            'permissions' => \App\Core\Rbac\RbacChecker::permissionsForRole($user->role),
        ];
    }
}

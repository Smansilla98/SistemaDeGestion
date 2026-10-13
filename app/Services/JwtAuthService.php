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
use App\Support\BranchAccess;
use App\Support\DemoLogin;
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
        private readonly JwtTokenService $jwt,
        private readonly BranchAccess $branches
    ) {}

    public function login(LoginCredentialsDto $dto, ?Request $request = null): AuthTokenPayloadDto
    {
        if ($dto->username === '' || $dto->password === '') {
            throw new ApiException('Usuario y contraseña son obligatorios.', 422, 'VALIDATION_ERROR');
        }

        DemoLogin::abortIfMatched($dto->username, $dto->password);

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

        // La sucursal elegida sobrevive al refresh mientras el usuario siga teniendo acceso.
        $branchId = $row->restaurant_id !== null ? (int) $row->restaurant_id : null;
        if ($branchId !== null && ! $this->branches->canAccess($user, $branchId)) {
            $branchId = null;
        }

        return $this->buildTokenResponse($user, $request, $branchId);
    }

    /**
     * Cambia la sucursal activa: emite un par de tokens nuevo atado a esa sucursal.
     */
    public function selectBranch(User $user, int $restaurantId, ?string $plainRefreshToken = null, ?Request $request = null): AuthTokenPayloadDto
    {
        if (! $this->branches->canAccess($user, $restaurantId)) {
            throw new ApiException('No tenés acceso a esa sucursal.', 403, 'BRANCH_FORBIDDEN');
        }

        $this->logout($plainRefreshToken);

        return $this->buildTokenResponse($user, $request, $restaurantId);
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
    public function me(User $user, bool $branchSelected = false): array
    {
        return $this->userToPublicArray($user, $branchSelected);
    }

    private function buildTokenResponse(User $user, ?Request $request = null, ?int $branchId = null): AuthTokenPayloadDto
    {
        $user->useRestaurant($branchId);

        $issued = $this->jwt->issueForUser($user, $branchId !== null);
        [$plainRefresh, $refreshTtl] = $this->issueRefreshToken($user, $request, $branchId);

        return new AuthTokenPayloadDto(
            accessToken: $issued['token'],
            tokenType: 'Bearer',
            expiresIn: $issued['expires_in'],
            user: $this->userToPublicArray($user, $branchId !== null),
            refreshToken: $plainRefresh,
            refreshExpiresIn: $refreshTtl,
        );
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function issueRefreshToken(User $user, ?Request $request = null, ?int $branchId = null): array
    {
        $ttl = (int) config('jwt.refresh_ttl', 60 * 60 * 24 * 30);
        $plain = Str::random(64);

        RefreshToken::query()->create([
            'user_id' => $user->id,
            'restaurant_id' => $branchId,
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
    private function userToPublicArray(User $user, bool $branchSelected = false): array
    {
        $branches = $this->branches->branchesFor($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'restaurant_id' => $user->restaurant_id,
            'restaurant_name' => $branches->firstWhere('id', $user->restaurant_id)?->name,
            'home_restaurant_id' => $user->homeRestaurantId(),
            'branches' => BranchAccess::toPublicArray($branches),
            'requires_branch_selection' => $branches->count() > 1 && ! $branchSelected,
            'is_active' => (bool) $user->is_active,
            'permissions' => \App\Core\Rbac\RbacChecker::permissionsForRole($user->role),
            'modules' => app(\App\Services\ModuleLicenseService::class)->mapFor($user->restaurant_id),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\DTO\Pagination\PaginationQueryDto;
use App\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use InvalidArgumentException;

/**
 * Alta y mantenimiento de usuarios del restaurante vía repositorio PDO.
 * Contraseñas con password_hash (compatible con password_verify / Hash de Laravel).
 */
final class UserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Logger $logger
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForRestaurant(int $restaurantId): array
    {
        return $this->users->findAllByRestaurant($restaurantId);
    }

    public function paginateForRestaurant(int $restaurantId, PaginationQueryDto $pagination): LengthAwarePaginator
    {
        $total = $this->users->countByRestaurant($restaurantId);
        $items = $this->users->findPageByRestaurant($restaurantId, $pagination->page, $pagination->perPage);

        return new LengthAwarePaginator(
            $items,
            $total,
            $pagination->perPage,
            $pagination->page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getById(int $id, int $restaurantId): array
    {
        $row = $this->users->findByIdForRestaurant($id, $restaurantId);
        if ($row === null) {
            throw new InvalidArgumentException('Usuario no encontrado');
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $input  validado (incluye password en claro)
     * @return array<string, mixed>
     */
    public function create(int $restaurantId, array $input): array
    {
        $email = $input['email'] ?? ($input['username'].'@restaurant.internal');
        $hash = password_hash((string) $input['password'], PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new \RuntimeException('No se pudo generar el hash de contraseña');
        }

        $row = $this->users->create([
            'restaurant_id' => $restaurantId,
            'name' => strip_tags((string) $input['name']),
            'username' => strip_tags((string) $input['username']),
            'email' => $email,
            'password' => $hash,
            'role' => $input['role'],
            'is_active' => $input['is_active'] ?? true,
        ]);

        if ($row === null) {
            throw new \RuntimeException('No se pudo crear el usuario');
        }

        $this->logger->info('Usuario creado vía API', ['user_id' => $row['id']]);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, int $restaurantId, array $input): array
    {
        if ($this->users->findByIdForRestaurant($id, $restaurantId) === null) {
            throw new InvalidArgumentException('Usuario no encontrado');
        }

        $data = [
            'name' => isset($input['name']) ? strip_tags((string) $input['name']) : null,
            'username' => isset($input['username']) ? strip_tags((string) $input['username']) : null,
            'email' => $input['email'] ?? null,
            'role' => $input['role'] ?? null,
            'is_active' => $input['is_active'] ?? null,
        ];

        if (! empty($input['password'])) {
            $hash = password_hash((string) $input['password'], PASSWORD_DEFAULT);
            if ($hash === false) {
                throw new \RuntimeException('No se pudo generar el hash de contraseña');
            }
            $data['password'] = $hash;
        }

        $data = array_filter($data, static fn ($v) => $v !== null);

        if ($data !== []) {
            $this->users->update($id, $restaurantId, $data);
        }

        return $this->getById($id, $restaurantId);
    }

    public function delete(int $id, int $restaurantId, int $actorId): void
    {
        if ($id === $actorId) {
            throw new InvalidArgumentException('No podés eliminar tu propio usuario');
        }

        if ($this->users->findByIdForRestaurant($id, $restaurantId) === null) {
            throw new InvalidArgumentException('Usuario no encontrado');
        }

        $this->users->delete($id, $restaurantId);
        $this->logger->info('Usuario eliminado vía API', ['deleted_id' => $id, 'actor_id' => $actorId]);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\DTO\Pagination\PaginationQueryDto;
use App\Repositories\ClientRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use InvalidArgumentException;

/**
 * Gestión de clientes del restaurante (tabla clients) a través del repositorio PDO.
 */
final class ClientService
{
    public function __construct(
        private readonly ClientRepository $clients,
        private readonly Logger $logger
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForRestaurant(int $restaurantId): array
    {
        return $this->clients->findAllByRestaurant($restaurantId);
    }

    public function paginateForRestaurant(int $restaurantId, PaginationQueryDto $pagination): LengthAwarePaginator
    {
        $total = $this->clients->countByRestaurant($restaurantId);
        $items = $this->clients->findPageByRestaurant($restaurantId, $pagination->page, $pagination->perPage);

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
        $row = $this->clients->findByIdForRestaurant($id, $restaurantId);
        if ($row === null) {
            throw new InvalidArgumentException('Cliente no encontrado');
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function create(int $restaurantId, array $input): array
    {
        $data = [
            'restaurant_id' => $restaurantId,
            'name' => strip_tags((string) $input['name']),
            'phone' => isset($input['phone']) ? strip_tags((string) $input['phone']) : null,
            'email' => isset($input['email']) ? filter_var($input['email'], FILTER_SANITIZE_EMAIL) : null,
            'notes' => isset($input['notes']) ? strip_tags((string) $input['notes']) : null,
        ];

        try {
            $row = $this->clients->create($data);
            $this->logger->info('Cliente creado vía API', ['client_id' => $row['id']]);

            return $row;
        } catch (\Throwable $e) {
            $this->logger->error('Error al crear cliente', [], $e);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, int $restaurantId, array $input): array
    {
        if ($this->clients->findByIdForRestaurant($id, $restaurantId) === null) {
            throw new InvalidArgumentException('Cliente no encontrado');
        }

        $data = array_filter([
            'name' => isset($input['name']) ? strip_tags((string) $input['name']) : null,
            'phone' => isset($input['phone']) ? strip_tags((string) $input['phone']) : null,
            'email' => isset($input['email']) ? filter_var($input['email'], FILTER_SANITIZE_EMAIL) : null,
            'notes' => isset($input['notes']) ? strip_tags((string) $input['notes']) : null,
        ], static fn ($v) => $v !== null);

        if ($data !== []) {
            $this->clients->update($id, $restaurantId, $data);
        }

        return $this->getById($id, $restaurantId);
    }

    public function delete(int $id, int $restaurantId): void
    {
        if ($this->clients->findByIdForRestaurant($id, $restaurantId) === null) {
            throw new InvalidArgumentException('Cliente no encontrado');
        }

        $this->clients->delete($id, $restaurantId);
    }
}

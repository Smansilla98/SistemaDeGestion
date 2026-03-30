<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\DTO\Pagination\PaginationQueryDto;
use App\Repositories\ProductRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use InvalidArgumentException;

/**
 * Casos de uso de productos para la API REST (orquesta el repositorio PDO).
 */
final class ProductService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly Logger $logger
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForRestaurant(int $restaurantId, array $filters = []): array
    {
        $filters = $this->sanitizeFilters($filters);

        return $this->products->findAllByRestaurant($restaurantId, $filters);
    }

    public function paginateForRestaurant(int $restaurantId, array $filters, PaginationQueryDto $pagination): LengthAwarePaginator
    {
        $filters = $this->sanitizeFilters($filters);
        $total = $this->products->countByRestaurant($restaurantId, $filters);
        $items = $this->products->findPageByRestaurant($restaurantId, $filters, $pagination->page, $pagination->perPage);

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
        $row = $this->products->findByIdForRestaurant($id, $restaurantId);
        if ($row === null) {
            throw new InvalidArgumentException('Producto no encontrado');
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $input  datos ya validados por Form Request
     * @return array<string, mixed>
     */
    public function create(int $restaurantId, array $input): array
    {
        $data = $this->normalizePayload($restaurantId, $input);

        try {
            return $this->products->create($data);
        } catch (\Throwable $e) {
            $this->logger->error('Error al crear producto', [], $e);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, int $restaurantId, array $input): array
    {
        if ($this->products->findByIdForRestaurant($id, $restaurantId) === null) {
            throw new InvalidArgumentException('Producto no encontrado');
        }

        $data = $this->normalizePayload($restaurantId, $input, partial: true);
        $this->products->update($id, $restaurantId, $data);

        return $this->getById($id, $restaurantId);
    }

    public function delete(int $id, int $restaurantId): void
    {
        if ($this->products->findByIdForRestaurant($id, $restaurantId) === null) {
            throw new InvalidArgumentException('Producto no encontrado');
        }

        if ($this->products->countOrderItems($id) > 0) {
            throw new InvalidArgumentException('No se puede eliminar un producto con ítems de pedido asociados');
        }

        $this->products->delete($id, $restaurantId);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePayload(int $restaurantId, array $input, bool $partial = false): array
    {
        $keys = [
            'category_id', 'name', 'description', 'price', 'image',
            'has_stock', 'stock_minimum', 'is_active', 'type', 'unit', 'unit_cost', 'supplier_id',
        ];

        if ($partial) {
            $merged = ['restaurant_id' => $restaurantId];
            foreach ($keys as $key) {
                if (array_key_exists($key, $input)) {
                    $merged[$key] = $input[$key];
                }
            }
        } else {
            $merged = array_merge([
                'restaurant_id' => $restaurantId,
                'category_id' => null,
                'name' => '',
                'description' => null,
                'price' => 0,
                'image' => null,
                'has_stock' => false,
                'stock_minimum' => 0,
                'is_active' => true,
                'type' => 'PRODUCT',
                'unit' => null,
                'unit_cost' => null,
                'supplier_id' => null,
            ], $input);
        }

        if (array_key_exists('name', $merged)) {
            $merged['name'] = strip_tags((string) $merged['name']);
        }
        if (array_key_exists('description', $merged) && $merged['description'] !== null) {
            $merged['description'] = strip_tags((string) $merged['description']);
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function sanitizeFilters(array $filters): array
    {
        $out = [];
        if (isset($filters['type'])) {
            $out['type'] = $filters['type'];
        }
        if (isset($filters['category_id'])) {
            $out['category_id'] = $filters['category_id'];
        }
        if (isset($filters['is_active'])) {
            $out['is_active'] = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
        }
        if (! empty($filters['search'])) {
            $out['search'] = strip_tags((string) $filters['search']);
        }

        return $out;
    }
}

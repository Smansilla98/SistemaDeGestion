<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\DTO\Pagination\PaginationQueryDto;
use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use InvalidArgumentException;

/**
 * API REST de pedidos: lecturas con PDO; escrituras con OrderService / Eloquent
 * para conservar notificaciones, observers y reglas de negocio existentes.
 */
final class OrderRestService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly OrderService $orderService,
        private readonly Logger $logger
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForRestaurant(int $restaurantId, array $filters = []): array
    {
        return $this->orders->findAllByRestaurant($restaurantId, $filters);
    }

    public function paginateForRestaurant(int $restaurantId, array $filters, PaginationQueryDto $pagination): LengthAwarePaginator
    {
        $total = $this->orders->countByRestaurant($restaurantId, $filters);
        $items = $this->orders->findPageByRestaurant($restaurantId, $filters, $pagination->page, $pagination->perPage);

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
        $order = Order::with(['table', 'user', 'items.product', 'items.modifiers'])
            ->where('restaurant_id', $restaurantId)
            ->find($id);

        if ($order === null) {
            throw new InvalidArgumentException('Pedido no encontrado');
        }

        return [
            'order' => $order->toArray(),
            'items' => $order->items->map->toArray()->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(int $restaurantId, int $userId, array $input): Order
    {
        $payload = [
            'restaurant_id' => $restaurantId,
            'user_id' => $userId,
            'table_id' => $input['table_id'] ?? null,
            'subsector_item_id' => $input['subsector_item_id'] ?? null,
            'observations' => isset($input['observations']) ? strip_tags((string) $input['observations']) : null,
            'customer_name' => isset($input['customer_name']) ? strip_tags((string) $input['customer_name']) : null,
        ];

        try {
            return $this->orderService->createOrder($payload);
        } catch (\Throwable $e) {
            $this->logger->error('Error al crear pedido vía API', [], $e);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, int $restaurantId, array $input): Order
    {
        $order = Order::where('restaurant_id', $restaurantId)->find($id);
        if ($order === null) {
            throw new InvalidArgumentException('Pedido no encontrado');
        }

        $allowed = array_intersect_key($input, array_flip([
            'status', 'observations', 'customer_name', 'subtotal', 'discount', 'total',
        ]));

        if (isset($allowed['observations'])) {
            $allowed['observations'] = strip_tags((string) $allowed['observations']);
        }
        if (isset($allowed['customer_name'])) {
            $allowed['customer_name'] = strip_tags((string) $allowed['customer_name']);
        }

        if ($allowed !== []) {
            $order->update($allowed);
        }

        return $order->fresh(['table', 'user', 'items.product']);
    }

    public function delete(int $id, int $restaurantId): void
    {
        $order = Order::where('restaurant_id', $restaurantId)->find($id);
        if ($order === null) {
            throw new InvalidArgumentException('Pedido no encontrado');
        }

        $user = auth()->user();
        $isAdmin = $user && in_array($user->role, ['ADMIN', 'GERENTE'], true);

        if (! $isAdmin) {
            if (! in_array($order->status, ['ABIERTO', 'EN_PREPARACION', 'CANCELADO'], true)) {
                throw new InvalidArgumentException('Solo se pueden eliminar pedidos ABIERTO, EN_PREPARACION o CANCELADO');
            }
            if ($order->payments()->count() > 0) {
                throw new InvalidArgumentException('No se puede eliminar un pedido con pagos asociados');
            }
        }

        $order->items()->delete();
        $order->delete();
    }
}

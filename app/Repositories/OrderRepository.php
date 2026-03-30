<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Pedidos: consultas y mutaciones con sentencias preparadas.
 */
class OrderRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public static function make(): self
    {
        return new self(Database::connection());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findAllByRestaurant(int $restaurantId, array $filters = [], int $limit = 100, int $offset = 0): array
    {
        [$sql, $params] = $this->buildOrderFilterQuery($restaurantId, $filters, countOnly: false);
        $sql .= ' ORDER BY created_at DESC LIMIT :lim OFFSET :off';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(
                sprintf(':%s', $k),
                $v,
                is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countByRestaurant(int $restaurantId, array $filters = []): int
    {
        [$sql, $params] = $this->buildOrderFilterQuery($restaurantId, $filters, countOnly: true);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findPageByRestaurant(int $restaurantId, array $filters, int $page, int $perPage): array
    {
        [$sql, $params] = $this->buildOrderFilterQuery($restaurantId, $filters, countOnly: false);
        $sql .= ' ORDER BY created_at DESC LIMIT :lim OFFSET :off';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(
                sprintf(':%s', $k),
                $v,
                is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
        $offset = max(0, ($page - 1) * $perPage);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildOrderFilterQuery(int $restaurantId, array $filters, bool $countOnly): array
    {
        $sql = $countOnly
            ? 'SELECT COUNT(*) FROM orders WHERE restaurant_id = :rid'
            : 'SELECT * FROM orders WHERE restaurant_id = :rid';
        $params = ['rid' => $restaurantId];

        if (! empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        if (isset($filters['table_id']) && $filters['table_id'] !== '') {
            $sql .= ' AND table_id = :tid';
            $params['tid'] = (int) $filters['table_id'];
        }

        return [$sql, $params];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByIdForRestaurant(int $id, int $restaurantId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM orders WHERE id = :id AND restaurant_id = :rid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'rid' => $restaurantId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): array
    {
        $sql = <<<'SQL'
            INSERT INTO orders (
                restaurant_id, table_id, subsector_item_id, table_session_id, user_id,
                number, status, subtotal, discount, total, observations, customer_name,
                created_at, updated_at
            ) VALUES (
                :restaurant_id, :table_id, :subsector_item_id, :table_session_id, :user_id,
                :number, :status, :subtotal, :discount, :total, :observations, :customer_name,
                NOW(), NOW()
            )
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'table_id' => $data['table_id'],
            'subsector_item_id' => $data['subsector_item_id'],
            'table_session_id' => $data['table_session_id'],
            'user_id' => $data['user_id'],
            'number' => $data['number'],
            'status' => $data['status'] ?? 'ABIERTO',
            'subtotal' => $data['subtotal'] ?? '0.00',
            'discount' => $data['discount'] ?? '0.00',
            'total' => $data['total'] ?? '0.00',
            'observations' => $data['observations'],
            'customer_name' => $data['customer_name'],
        ]);

        $newId = (int) $this->pdo->lastInsertId();

        return $this->findByIdForRestaurant($newId, (int) $data['restaurant_id']) ?? [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, int $restaurantId, array $data): bool
    {
        $allowed = [
            'table_id', 'subsector_item_id', 'table_session_id', 'status',
            'subtotal', 'discount', 'total', 'observations', 'customer_name',
            'sent_at', 'closed_at',
        ];
        $sets = [];
        $params = ['id' => $id, 'rid' => $restaurantId];

        foreach ($allowed as $col) {
            if (! array_key_exists($col, $data)) {
                continue;
            }
            $ph = 'u_'.$col;
            $sets[] = "{$col} = :{$ph}";
            $params[$ph] = $data[$col];
        }

        if ($sets === []) {
            return false;
        }

        $sets[] = 'updated_at = NOW()';
        $sql = 'UPDATE orders SET '.implode(', ', $sets)
            .' WHERE id = :id AND restaurant_id = :rid';

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id, int $restaurantId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM orders WHERE id = :id AND restaurant_id = :rid'
        );

        return $stmt->execute(['id' => $id, 'rid' => $restaurantId]);
    }

    /**
     * Último número de pedido del año actual para generar el siguiente correlativo.
     */
    public function findLastOrderNumberForYear(int $restaurantId, string $prefix): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT number FROM orders WHERE restaurant_id = :rid AND number LIKE :pfx ORDER BY number DESC LIMIT 1'
        );
        $stmt->execute(['rid' => $restaurantId, 'pfx' => $prefix.'%']);
        $row = $stmt->fetch();

        return $row !== false ? (string) $row['number'] : null;
    }
}

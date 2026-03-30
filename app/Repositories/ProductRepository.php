<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Acceso a datos de productos mediante PDO y consultas parametrizadas.
 */
class ProductRepository
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
    public function findAllByRestaurant(int $restaurantId, array $filters = []): array
    {
        [$sql, $params] = $this->buildProductFilterQuery($restaurantId, $filters, countOnly: false);
        $sql .= ' ORDER BY p.name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function countByRestaurant(int $restaurantId, array $filters = []): int
    {
        [$sql, $params] = $this->buildProductFilterQuery($restaurantId, $filters, countOnly: true);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findPageByRestaurant(int $restaurantId, array $filters, int $page, int $perPage): array
    {
        [$sql, $params] = $this->buildProductFilterQuery($restaurantId, $filters, countOnly: false);
        $sql .= ' ORDER BY p.name ASC LIMIT :lim OFFSET :off';
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
    private function buildProductFilterQuery(int $restaurantId, array $filters, bool $countOnly): array
    {
        $sql = $countOnly
            ? 'SELECT COUNT(*) FROM products p WHERE p.restaurant_id = :rid'
            : 'SELECT p.* FROM products p WHERE p.restaurant_id = :rid';
        $params = ['rid' => $restaurantId];

        if (isset($filters['type']) && in_array($filters['type'], ['PRODUCT', 'INSUMO'], true)) {
            $sql .= ' AND p.type = :type';
            $params['type'] = $filters['type'];
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $sql .= ' AND p.category_id = :cid';
            $params['cid'] = (int) $filters['category_id'];
        }

        if (isset($filters['is_active'])) {
            $sql .= ' AND p.is_active = :active';
            $params['active'] = $filters['is_active'] ? 1 : 0;
        }

        if (! empty($filters['search'])) {
            $sql .= ' AND (p.name LIKE :q OR p.description LIKE :q2)';
            $term = '%'.$this->likeEscape((string) $filters['search']).'%';
            $params['q'] = $term;
            $params['q2'] = $term;
        }

        return [$sql, $params];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByIdForRestaurant(int $id, int $restaurantId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM products WHERE id = :id AND restaurant_id = :rid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'rid' => $restaurantId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed> fila insertada (sin relaciones)
     */
    public function create(array $data): array
    {
        $sql = <<<'SQL'
            INSERT INTO products (
                restaurant_id, category_id, name, description, price, image,
                has_stock, stock_minimum, is_active, type, unit, unit_cost, supplier_id,
                created_at, updated_at
            ) VALUES (
                :restaurant_id, :category_id, :name, :description, :price, :image,
                :has_stock, :stock_minimum, :is_active, :type, :unit, :unit_cost, :supplier_id,
                NOW(), NOW()
            )
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? 0,
            'image' => $data['image'] ?? null,
            'has_stock' => ! empty($data['has_stock']) ? 1 : 0,
            'stock_minimum' => (int) ($data['stock_minimum'] ?? 0),
            'is_active' => ! empty($data['is_active']) ? 1 : 0,
            'type' => $data['type'] ?? 'PRODUCT',
            'unit' => $data['unit'] ?? null,
            'unit_cost' => $data['unit_cost'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
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
            'category_id', 'name', 'description', 'price', 'image',
            'has_stock', 'stock_minimum', 'is_active', 'type', 'unit', 'unit_cost', 'supplier_id',
        ];
        $sets = [];
        $params = ['id' => $id, 'rid' => $restaurantId];

        foreach ($allowed as $col) {
            if (! array_key_exists($col, $data)) {
                continue;
            }
            $ph = 'u_'.$col;
            $sets[] = "{$col} = :{$ph}";
            $val = $data[$col];
            if (in_array($col, ['has_stock', 'is_active'], true)) {
                $params[$ph] = $val ? 1 : 0;
            } elseif (in_array($col, ['stock_minimum'], true)) {
                $params[$ph] = (int) $val;
            } else {
                $params[$ph] = $val;
            }
        }

        if ($sets === []) {
            return false;
        }

        $sets[] = 'updated_at = NOW()';
        $sql = 'UPDATE products SET '.implode(', ', $sets)
            .' WHERE id = :id AND restaurant_id = :rid';

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id, int $restaurantId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM products WHERE id = :id AND restaurant_id = :rid'
        );

        return $stmt->execute(['id' => $id, 'rid' => $restaurantId]);
    }

    public function countOrderItems(int $productId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = :pid');
        $stmt->execute(['pid' => $productId]);

        return (int) $stmt->fetchColumn();
    }

    private function likeEscape(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Repositorio de clientes (tabla clients).
 */
class ClientRepository
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
    public function findAllByRestaurant(int $restaurantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM clients WHERE restaurant_id = :rid ORDER BY name ASC'
        );
        $stmt->execute(['rid' => $restaurantId]);

        return $stmt->fetchAll();
    }

    public function countByRestaurant(int $restaurantId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM clients WHERE restaurant_id = :rid'
        );
        $stmt->execute(['rid' => $restaurantId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findPageByRestaurant(int $restaurantId, int $page, int $perPage): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $stmt = $this->pdo->prepare(
            'SELECT * FROM clients WHERE restaurant_id = :rid ORDER BY name ASC LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue(':rid', $restaurantId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByIdForRestaurant(int $id, int $restaurantId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM clients WHERE id = :id AND restaurant_id = :rid LIMIT 1'
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
        $stmt = $this->pdo->prepare(
            'INSERT INTO clients (restaurant_id, name, phone, email, notes, created_at, updated_at)
             VALUES (:restaurant_id, :name, :phone, :email, :notes, NOW(), NOW())'
        );
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'notes' => $data['notes'],
        ]);

        $newId = (int) $this->pdo->lastInsertId();

        return $this->findByIdForRestaurant($newId, (int) $data['restaurant_id']) ?? [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, int $restaurantId, array $data): bool
    {
        $allowed = ['name', 'phone', 'email', 'notes'];
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
        $sql = 'UPDATE clients SET '.implode(', ', $sets)
            .' WHERE id = :id AND restaurant_id = :rid';

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id, int $restaurantId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM clients WHERE id = :id AND restaurant_id = :rid'
        );

        return $stmt->execute(['id' => $id, 'rid' => $restaurantId]);
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Acceso a datos de usuarios con PDO; contraseñas solo como hash (password_hash).
 */
class UserRepository
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
            'SELECT id, restaurant_id, name, username, email, role, is_active, last_login_at, created_at, updated_at
             FROM users WHERE restaurant_id = :rid ORDER BY name ASC'
        );
        $stmt->execute(['rid' => $restaurantId]);

        return $stmt->fetchAll();
    }

    public function countByRestaurant(int $restaurantId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM users WHERE restaurant_id = :rid'
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
            'SELECT id, restaurant_id, name, username, email, role, is_active, last_login_at, created_at, updated_at
             FROM users WHERE restaurant_id = :rid ORDER BY name ASC LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue(':rid', $restaurantId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null sin password
     */
    public function findByIdForRestaurant(int $id, int $restaurantId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, restaurant_id, name, username, email, role, is_active, last_login_at, created_at, updated_at
             FROM users WHERE id = :id AND restaurant_id = :rid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'rid' => $restaurantId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null incluye password (login JWT / username único)
     */
    public function findByUsernameWithPassword(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE username = :u LIMIT 1'
        );
        $stmt->execute(['u' => $username]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string, mixed>|null incluye password (solo uso interno / verificación)
     */
    public function findByIdWithPassword(int $id, int $restaurantId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE id = :id AND restaurant_id = :rid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'rid' => $restaurantId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @param  array<string, mixed>  $data  password debe ir ya hasheada
     * @return array<string, mixed>|null usuario sin password
     */
    public function create(array $data): ?array
    {
        $sql = <<<'SQL'
            INSERT INTO users (
                restaurant_id, name, username, email, password, role, is_active, created_at, updated_at
            ) VALUES (
                :restaurant_id, :name, :username, :email, :password, :role, :is_active, NOW(), NOW()
            )
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'restaurant_id' => $data['restaurant_id'],
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => ! empty($data['is_active']) ? 1 : 0,
        ]);

        $newId = (int) $this->pdo->lastInsertId();

        return $this->findByIdForRestaurant($newId, (int) $data['restaurant_id']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, int $restaurantId, array $data): bool
    {
        $allowed = ['name', 'username', 'email', 'role', 'is_active'];
        $sets = [];
        $params = ['id' => $id, 'rid' => $restaurantId];

        foreach ($allowed as $col) {
            if (! array_key_exists($col, $data)) {
                continue;
            }
            $ph = 'u_'.$col;
            $sets[] = "{$col} = :{$ph}";
            $params[$ph] = $col === 'is_active' ? (! empty($data[$col]) ? 1 : 0) : $data[$col];
        }

        if (isset($data['password']) && $data['password'] !== '') {
            $sets[] = 'password = :u_password';
            $params['u_password'] = $data['password'];
        }

        if ($sets === []) {
            return false;
        }

        $sets[] = 'updated_at = NOW()';
        $sql = 'UPDATE users SET '.implode(', ', $sets)
            .' WHERE id = :id AND restaurant_id = :rid';

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id, int $restaurantId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM users WHERE id = :id AND restaurant_id = :rid'
        );

        return $stmt->execute(['id' => $id, 'rid' => $restaurantId]);
    }
}

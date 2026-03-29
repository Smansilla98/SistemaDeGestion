<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Logger;
use App\Repositories\UserRepository;
use App\Services\UserService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias del servicio de usuarios (repositorio mockeado).
 */
class UserServiceTest extends TestCase
{
    public function test_create_hashes_password_and_calls_repository(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $row): bool {
                $this->assertArrayHasKey('password', $row);
                $this->assertNotSame('secret123', $row['password']);
                $this->assertTrue(password_verify('secret123', $row['password']));

                return $row['restaurant_id'] === 5 && $row['username'] === 'mozo1';
            }))
            ->willReturn(['id' => 7, 'username' => 'mozo1', 'name' => 'Mozo']);

        $logger = $this->createMock(Logger::class);
        $service = new UserService($repo, $logger);

        $out = $service->create(5, [
            'name' => 'Mozo',
            'username' => 'mozo1',
            'password' => 'secret123',
            'role' => 'MOZO',
            'is_active' => true,
        ]);

        $this->assertSame(7, $out['id']);
    }

    public function test_delete_same_user_throws(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $service = new UserService($repo, $this->createMock(Logger::class));

        $this->expectException(InvalidArgumentException::class);
        $service->delete(3, 1, 3);
    }
}

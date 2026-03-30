<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Logger;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias del servicio de productos (mocks del repositorio PDO).
 */
class ProductServiceTest extends TestCase
{
    public function test_list_for_restaurant_delegates_to_repository(): void
    {
        $repo = $this->createMock(ProductRepository::class);
        $repo->expects($this->once())
            ->method('findAllByRestaurant')
            ->with(10, ['type' => 'PRODUCT'])
            ->willReturn([['id' => 1, 'name' => 'Pizza']]);

        $logger = $this->createMock(Logger::class);
        $service = new ProductService($repo, $logger);

        $result = $service->listForRestaurant(10, ['type' => 'PRODUCT']);

        $this->assertCount(1, $result);
        $this->assertSame('Pizza', $result[0]['name']);
    }

    public function test_get_by_id_throws_when_missing(): void
    {
        $repo = $this->createMock(ProductRepository::class);
        $repo->method('findByIdForRestaurant')->willReturn(null);

        $service = new ProductService($repo, $this->createMock(Logger::class));

        $this->expectException(InvalidArgumentException::class);
        $service->getById(99, 10);
    }
}

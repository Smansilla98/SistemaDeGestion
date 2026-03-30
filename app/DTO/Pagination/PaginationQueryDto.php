<?php

declare(strict_types=1);

namespace App\DTO\Pagination;

/**
 * Parámetros de paginación normalizados desde la query string.
 */
final readonly class PaginationQueryDto
{
    public function __construct(
        public int $page,
        public int $perPage
    ) {}

    public static function fromRequest(?int $page, ?int $perPage, int $defaultPerPage = 15, int $maxPerPage = 100): self
    {
        $p = max(1, $page ?? 1);
        $pp = $perPage ?? $defaultPerPage;
        $pp = min($maxPerPage, max(1, $pp));

        return new self(page: $p, perPage: $pp);
    }
}

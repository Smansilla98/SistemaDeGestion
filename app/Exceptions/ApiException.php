<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Excepción HTTP prevista para la API (mensaje seguro para el cliente).
 */
final class ApiException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>|null  $errors
     */
    public function __construct(
        string $message = '',
        private readonly int $statusCode = 400,
        private readonly ?string $errorCode = null,
        private readonly ?array $errors = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, array<int, string>>|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }
}

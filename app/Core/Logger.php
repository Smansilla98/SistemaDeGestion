<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Logger de aplicación simple (archivo rotativo manual vía append).
 * Escribe en storage/logs/app.log además del canal default de Laravel si se desea.
 */
class Logger
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? storage_path('logs/app.log');
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    public function error(string $message, array $context = [], ?Throwable $e = null): void
    {
        if ($e !== null) {
            $context['exception'] = $e->getMessage();
            $context['trace'] = $e->getTraceAsString();
        }
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $dir = dirname($this->path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );

        file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }
}

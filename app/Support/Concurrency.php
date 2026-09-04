<?php

namespace App\Support;

use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class Concurrency
{
    /**
     * Ejecuta $callback dentro de transacción; reintenta en duplicate/deadlock/lock wait.
     */
    public static function retryOnConflict(int $attempts, callable $callback): mixed
    {
        $attempts = max(1, $attempts);

        for ($i = 1; ; $i++) {
            try {
                return DB::transaction($callback, 3);
            } catch (QueryException|DeadlockException $e) {
                $code = (int) ($e->errorInfo[1] ?? 0);
                $isConflict = in_array($code, [1062, 1213, 1205], true)
                    || str_contains($e->getMessage(), 'UNIQUE constraint failed')
                    || str_contains($e->getMessage(), 'database is locked');

                if (! $isConflict || $i >= $attempts) {
                    throw $e;
                }

                usleep(random_int(10_000, 60_000) * $i);
            }
        }
    }

    public static function isDuplicateKey(QueryException $e): bool
    {
        $code = (int) ($e->errorInfo[1] ?? 0);

        return $code === 1062
            || str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || str_contains($e->getMessage(), 'Duplicate entry');
    }
}

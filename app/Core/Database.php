<?php

declare(strict_types=1);

namespace App\Core;

use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use Throwable;

/**
 * PDO de la capa de repositorios.
 *
 * Es el MISMO PDO que usa Laravel (DB::connection()). Antes se abría una conexión
 * aparte: los repositorios no veían lo que Eloquent hacía dentro de una transacción
 * (ni en producción ni en los tests con RefreshDatabase) y una transacción de un lado
 * no protegía al otro. Ahora comparten conexión, transacciones y configuración.
 */
final class Database
{
    public static function connection(): PDO
    {
        try {
            $pdo = DB::connection()->getPdo();
        } catch (Throwable $e) {
            throw new RuntimeException('Error al conectar con la base de datos: '.$e->getMessage(), 0, $e);
        }

        // Los repositorios trabajan con arrays asociativos y excepciones. Laravel fija su
        // propio modo de fetch por sentencia, así que este default no lo afecta.
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    /**
     * Compatibilidad: la conexión la administra Laravel (DB::purge / DB::reconnect).
     */
    public static function resetConnection(): void
    {
        DB::purge();
    }
}

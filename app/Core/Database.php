<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexión PDO centralizada (singleton) para la capa de repositorios.
 * Usa la misma configuración que Laravel (config/database.php) para no duplicar credenciales.
 */
final class Database
{
    private static ?PDO $connection = null;

    /**
     * Obtiene la instancia única de PDO con prepared statements por defecto.
     */
    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $default = (string) config('database.default', 'mysql');
        $config = config("database.connections.{$default}");

        if (! is_array($config)) {
            throw new RuntimeException("No se encontró la conexión de base de datos [{$default}].");
        }

        $driver = $config['driver'] ?? 'mysql';

        try {
            if ($driver === 'sqlite') {
                $path = $config['database'] ?? database_path('database.sqlite');
                $dsn = 'sqlite:'.$path;
                $pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } else {
                $host = $config['host'] ?? '127.0.0.1';
                $port = (string) ($config['port'] ?? '3306');
                $database = $config['database'] ?? '';
                $username = $config['username'] ?? '';
                $password = $config['password'] ?? '';
                $charset = $config['charset'] ?? 'utf8mb4';

                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $host,
                    $port,
                    $database,
                    $charset
                );

                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            }
        } catch (PDOException $e) {
            throw new RuntimeException('Error al conectar con la base de datos: '.$e->getMessage(), 0, $e);
        }

        self::$connection = $pdo;

        return self::$connection;
    }

    /**
     * Solo para pruebas: libera la conexión y permite volver a crearla.
     */
    public static function resetConnection(): void
    {
        self::$connection = null;
    }
}

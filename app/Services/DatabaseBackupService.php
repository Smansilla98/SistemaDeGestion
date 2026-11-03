<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Backup de la base en SQL comprimido, sin depender de mysqldump (la imagen no lo trae).
 * Mismo enfoque que adminChilingo (App\Domain\Datos\Respaldo).
 *
 * Se guarda en el disco `local` (storage/app/private, dentro del volumen de Railway) bajo
 * `backups/`, junto a `backups/last.json` con el resultado del último intento.
 */
class DatabaseBackupService
{
    public const DIR = 'backups';

    public const MARK = 'backups/last.json';

    public const DISK = 'local';

    private const ROWS_PER_INSERT = 200;

    /**
     * El backup trae los datos de todas las organizaciones: lo baja el SUPERADMIN o, si en la
     * base hay una sola organización (instalaciones como Patuka), su ADMIN.
     */
    public static function canDownload(?User $user): bool
    {
        if ($user === null) {
            return false;
        }
        if ($user->role === User::ROLE_SUPERADMIN) {
            return true;
        }

        return $user->role === User::ROLE_ADMIN && Restaurant::query()->count() === 1;
    }

    /**
     * @return array{file: string, bytes: int, tables: int, rows: int, seconds: float}
     */
    public function create(int $keep = 14): array
    {
        $start = microtime(true);
        $tmp = tempnam(sys_get_temp_dir(), 'backup-');
        $gz = $tmp !== false ? gzopen($tmp, 'wb6') : false;
        if ($tmp === false || $gz === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del backup.');
        }

        try {
            [$tables, $rows] = $this->dump($gz);
        } catch (Throwable $e) {
            gzclose($gz);
            @unlink($tmp);
            $this->mark(['ok' => false, 'error' => $e->getMessage()]);
            throw $e;
        }
        gzclose($gz);

        $file = self::DIR.'/backup-'.now()->format('Ymd-His').'.sql.gz';
        $stream = fopen($tmp, 'rb');
        $saved = $stream !== false && Storage::disk(self::DISK)->writeStream($file, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        $bytes = (int) filesize($tmp);
        @unlink($tmp);

        if (! $saved) {
            $this->mark(['ok' => false, 'error' => 'No se pudo escribir en el disco.']);
            throw new RuntimeException('No se pudo escribir el backup en el disco.');
        }

        $this->prune($keep);
        $result = ['file' => $file, 'bytes' => $bytes, 'tables' => $tables, 'rows' => $rows, 'seconds' => round(microtime(true) - $start, 1)];
        $this->mark(['ok' => true] + $result);

        return $result;
    }

    /** @return array<string, mixed>|null resultado del último intento */
    public static function last(): ?array
    {
        try {
            $json = Storage::disk(self::DISK)->get(self::MARK);
        } catch (Throwable) {
            return null;
        }
        $data = $json ? json_decode($json, true) : null;

        return is_array($data) ? $data : null;
    }

    /** @return list<string> backups guardados, del más nuevo al más viejo */
    public static function files(): array
    {
        $list = array_values(array_filter(Storage::disk(self::DISK)->files(self::DIR), fn (string $f) => str_ends_with($f, '.sql.gz')));
        rsort($list);

        return $list;
    }

    /**
     * @param  resource  $gz
     * @return array{0: int, 1: int}
     */
    private function dump($gz): array
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $driver = $connection->getDriverName();
        $tables = $this->tables($driver);

        gzwrite($gz, '-- Backup '.config('app.name').' · '.now()->toIso8601String()." · {$driver}\n");
        if ($driver === 'mysql') {
            gzwrite($gz, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
            // Sin buffer: las tablas grandes se leen de a una fila, sin cargar todo en memoria.
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }

        $total = 0;
        try {
            foreach ($tables as $table) {
                $q = $this->quoteId($table, $driver);
                gzwrite($gz, "DROP TABLE IF EXISTS {$q};\n".$this->createTable($table, $driver).";\n");

                $stmt = $pdo->query("SELECT * FROM {$q}");
                if ($stmt === false) {
                    continue;
                }
                $batch = [];
                $columns = null;
                while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                    $columns ??= implode(', ', array_map(fn ($c) => $this->quoteId((string) $c, $driver), array_keys($row)));
                    $batch[] = '('.implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)).')';
                    if (count($batch) >= self::ROWS_PER_INSERT) {
                        gzwrite($gz, "INSERT INTO {$q} ({$columns}) VALUES\n".implode(",\n", $batch).";\n");
                        $total += count($batch);
                        $batch = [];
                    }
                }
                $stmt->closeCursor();
                if ($batch !== []) {
                    gzwrite($gz, "INSERT INTO {$q} ({$columns}) VALUES\n".implode(",\n", $batch).";\n");
                    $total += count($batch);
                }
                gzwrite($gz, "\n");
            }
        } finally {
            if ($driver === 'mysql') {
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            }
        }

        if ($driver === 'mysql') {
            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        }

        return [count($tables), $total];
    }

    /** @return list<string> */
    private function tables(string $driver): array
    {
        if ($driver === 'sqlite') {
            return array_map(fn ($r) => (string) $r->name, DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"));
        }

        return array_map(fn ($r) => (string) array_values((array) $r)[0], DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"));
    }

    private function createTable(string $table, string $driver): string
    {
        if ($driver === 'sqlite') {
            return (string) DB::selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $table])->sql;
        }
        $row = (array) DB::selectOne('SHOW CREATE TABLE '.$this->quoteId($table, $driver));

        return (string) ($row['Create Table'] ?? array_values($row)[1]);
    }

    private function quoteId(string $name, string $driver): string
    {
        return $driver === 'mysql'
            ? '`'.str_replace('`', '``', $name).'`'
            : '"'.str_replace('"', '""', $name).'"';
    }

    private function prune(int $keep): void
    {
        foreach (array_slice(self::files(), max(1, $keep)) as $old) {
            Storage::disk(self::DISK)->delete($old);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function mark(array $data): void
    {
        try {
            Storage::disk(self::DISK)->put(self::MARK, (string) json_encode(['date' => now()->toIso8601String()] + $data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } catch (Throwable) {
            // Si ni la marca se puede escribir, la pantalla lo muestra como «sin backups».
        }
    }
}

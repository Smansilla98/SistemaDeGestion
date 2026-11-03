<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep=14 : Cuántos backups conservar}';

    protected $description = 'Vuelca la base completa en SQL comprimido (storage/app/private/backups)';

    public function handle(DatabaseBackupService $backups): int
    {
        try {
            $r = $backups->create(max(1, (int) $this->option('keep')));
        } catch (Throwable $e) {
            $this->error('No se pudo hacer el backup: '.$e->getMessage());

            return self::FAILURE;
        }
        $this->info(sprintf('Backup %s: %d tablas, %d filas, %.1f MB en %.1f s.', $r['file'], $r['tables'], $r['rows'], $r['bytes'] / 1048576, $r['seconds']));

        return self::SUCCESS;
    }
}

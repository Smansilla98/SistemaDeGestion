<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Verifica (y opcionalmente repara) el esquema mínimo que necesita el cobro/cierre de mesas.
 *
 * Existe porque en bases donde las migraciones quedaron marcadas como corridas
 * (o se aplicaron con --pretend/fake) el esquema real queda desalineado y el cierre
 * de mesa falla con:
 *   - "Data truncated for column 'payment_method'"  (enum sin QR/MIXTO)
 *   - "Field 'action' doesn't have a default value" (audit_logs legacy, SQLSTATE 1364)
 *
 * Siempre devuelve 0 para no frenar el arranque del contenedor.
 */
class VerifyCheckoutSchema extends Command
{
    protected $signature = 'checkout:verify-schema {--fix : Aplicar las correcciones detectadas}';

    protected $description = 'Verifica el esquema crítico de cobro (payments.payment_method y audit_logs) y lo repara con --fix';

    private const PAYMENT_METHODS = ['EFECTIVO', 'DEBITO', 'CREDITO', 'TRANSFERENCIA', 'QR', 'MIXTO'];

    public function handle(): int
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->info("Driver '{$driver}': no hay enums nativos que verificar. Se omite.");

            return 0;
        }

        $fix = (bool) $this->option('fix');

        try {
            $this->checkPaymentsEnum($fix);
            $this->checkAuditLogs($fix);
        } catch (\Throwable $e) {
            $this->warn('No se pudo verificar el esquema de cobro: '.$e->getMessage());
        }

        AuditLog::forgetSchemaCache();

        return 0;
    }

    private function checkPaymentsEnum(bool $fix): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'payment_method')) {
            $this->warn('payments.payment_method no existe todavía. Se omite.');

            return;
        }

        $column = DB::select("SHOW COLUMNS FROM `payments` WHERE Field = 'payment_method'");

        if (empty($column)) {
            return;
        }

        $type = $column[0]->Type;
        $faltantes = [];

        foreach (self::PAYMENT_METHODS as $method) {
            if (stripos($type, "'".$method."'") === false) {
                $faltantes[] = $method;
            }
        }

        if (empty($faltantes)) {
            $this->info('✓ payments.payment_method acepta todos los métodos de pago.');

            return;
        }

        $this->warn('payments.payment_method no acepta: '.implode(', ', $faltantes));

        if (! $fix) {
            $this->line('  Ejecutá: php artisan checkout:verify-schema --fix');

            return;
        }

        $values = implode(',', array_map(fn ($m) => "'".$m."'", self::PAYMENT_METHODS));
        DB::statement("ALTER TABLE `payments` MODIFY `payment_method` ENUM({$values}) NOT NULL");
        $this->info('✓ payments.payment_method corregido.');
    }

    private function checkAuditLogs(bool $fix): void
    {
        if (! Schema::hasTable('audit_logs')) {
            $this->warn('audit_logs no existe todavía. Se omite.');

            return;
        }

        $nuevas = [
            'auditable_type' => 'VARCHAR(255) NULL',
            'auditable_id' => 'BIGINT UNSIGNED NULL',
            'event' => 'VARCHAR(255) NULL',
            'old_values' => 'JSON NULL',
            'new_values' => 'JSON NULL',
            'reason' => 'VARCHAR(255) NULL',
            'ip' => 'VARCHAR(45) NULL',
        ];

        $faltantes = [];
        foreach ($nuevas as $column => $definition) {
            if (! Schema::hasColumn('audit_logs', $column)) {
                $faltantes[] = $column;
            }
        }

        // `action` NOT NULL sin default es la causa exacta del SQLSTATE 1364.
        $legacyRigidas = [];
        foreach (['action', 'model_type'] as $column) {
            if (! Schema::hasColumn('audit_logs', $column)) {
                continue;
            }

            $info = DB::select("SHOW COLUMNS FROM `audit_logs` WHERE Field = '{$column}'");
            if (! empty($info) && strtoupper($info[0]->Null) === 'NO' && $info[0]->Default === null) {
                $legacyRigidas[] = $column;
            }
        }

        if (empty($faltantes) && empty($legacyRigidas)) {
            $this->info('✓ audit_logs está reconciliado (forma nueva + legacy nullable).');

            return;
        }

        if (! empty($faltantes)) {
            $this->warn('audit_logs: faltan columnas '.implode(', ', $faltantes));
        }
        if (! empty($legacyRigidas)) {
            $this->warn('audit_logs: columnas NOT NULL sin default '.implode(', ', $legacyRigidas));
        }

        if (! $fix) {
            $this->line('  Ejecutá: php artisan checkout:verify-schema --fix');

            return;
        }

        foreach ($faltantes as $column) {
            try {
                DB::statement("ALTER TABLE `audit_logs` ADD COLUMN `{$column}` {$nuevas[$column]}");
                $this->info("  ✓ columna {$column} agregada.");
            } catch (\Throwable $e) {
                $this->warn("  ⚠️  no se pudo agregar {$column}: ".$e->getMessage());
            }
        }

        foreach ($legacyRigidas as $column) {
            try {
                DB::statement("ALTER TABLE `audit_logs` MODIFY `{$column}` VARCHAR(255) NULL");
                $this->info("  ✓ {$column} ahora es nullable.");
            } catch (\Throwable $e) {
                $this->warn("  ⚠️  no se pudo aflojar {$column}: ".$e->getMessage());
            }
        }

        // Backfill entre ambas formas para que los reportes no queden con huecos.
        foreach ([
            'auditable_type' => 'model_type',
            'auditable_id' => 'model_id',
            'event' => 'action',
            'new_values' => 'changes',
            'ip' => 'ip_address',
        ] as $target => $source) {
            if (! Schema::hasColumn('audit_logs', $target) || ! Schema::hasColumn('audit_logs', $source)) {
                continue;
            }

            try {
                DB::statement("UPDATE `audit_logs` SET `{$target}` = `{$source}` WHERE `{$target}` IS NULL AND `{$source}` IS NOT NULL");
            } catch (\Throwable $e) {
                // Ignorable.
            }
        }

        $this->info('✓ audit_logs reparado.');
    }
}

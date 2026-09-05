<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reconcilia las dos formas históricas de `audit_logs`.
 *
 * La tabla original (2024_01_01_000017) tiene `action` NOT NULL + model_type/model_id/changes.
 * La capa de integridad (2026_09_04_120000) sólo crea la forma nueva
 * (auditable_type/event/old_values/new_values/reason/ip) cuando la tabla NO existe,
 * así que en bases ya instaladas conviven código nuevo con esquema viejo y el
 * INSERT del OrderObserver revienta con:
 *   SQLSTATE[HY000]: General error: 1364 Field 'action' doesn't have a default value
 * lo que hace rollback de toda la transacción de cobro y deja la mesa sin cerrar.
 *
 * Esta migración es idempotente y NO borra las columnas legacy (ModuleUsageService
 * sigue leyendo action/model_type/changes).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        // 1) Agregar las columnas de la forma nueva que falten.
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'auditable_type')) {
                $table->string('auditable_type')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('audit_logs', 'auditable_id')) {
                $table->unsignedBigInteger('auditable_id')->nullable()->after('auditable_type');
            }
            if (! Schema::hasColumn('audit_logs', 'event')) {
                $table->string('event')->nullable()->after('auditable_id');
            }
            if (! Schema::hasColumn('audit_logs', 'old_values')) {
                $table->json('old_values')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'new_values')) {
                $table->json('new_values')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'reason')) {
                $table->string('reason')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'ip')) {
                $table->string('ip', 45)->nullable();
            }
        });

        $driver = DB::connection()->getDriverName();

        // 2) Aflojar las columnas legacy NOT NULL (causa directa del 1364).
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            foreach (['action' => 'VARCHAR(255)', 'model_type' => 'VARCHAR(255)'] as $column => $type) {
                if (! Schema::hasColumn('audit_logs', $column)) {
                    continue;
                }

                try {
                    DB::statement("ALTER TABLE `audit_logs` MODIFY `{$column}` {$type} NULL");
                } catch (\Throwable $e) {
                    // No bloquear el deploy por esto.
                }
            }
        }

        // 3) Backfill entre ambas formas (sólo donde falte el dato).
        $this->backfill('auditable_type', 'model_type');
        $this->backfill('auditable_id', 'model_id');
        $this->backfill('event', 'action');
        $this->backfill('new_values', 'changes');
        $this->backfill('ip', 'ip_address');

        // 4) Índice de la forma nueva.
        if (Schema::hasColumn('audit_logs', 'auditable_type') && Schema::hasColumn('audit_logs', 'auditable_id')) {
            try {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index(['auditable_type', 'auditable_id', 'created_at'], 'audit_logs_auditable_created');
                });
            } catch (\Throwable $e) {
                // Ya existe.
            }
        }
    }

    public function down(): void
    {
        // Sin reversa destructiva a propósito: sólo se agregaron columnas nullable
        // y se relajaron restricciones. Revertir borraría auditoría.
    }

    private function backfill(string $target, string $source): void
    {
        if (! Schema::hasColumn('audit_logs', $target) || ! Schema::hasColumn('audit_logs', $source)) {
            return;
        }

        try {
            DB::statement("UPDATE `audit_logs` SET `{$target}` = `{$source}` WHERE `{$target}` IS NULL AND `{$source}` IS NOT NULL");
        } catch (\Throwable $e) {
            // Datos raros / tipos incompatibles: no frenar el deploy.
        }
    }
};

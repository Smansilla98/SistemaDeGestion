<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Post-migrate: alinea secuencias, limpia sesiones abiertas duplicadas, backfill cents.
 */
class ConurbaniaRepairIntegrity extends Command
{
    protected $signature = 'conurbania:repair {--force : Ejecutar sin preguntar}';

    protected $description = 'Repara integridad Conurbania (secuencias, sesiones, snapshots de precio)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('¿Ejecutar reparación de integridad?', true)) {
            return self::SUCCESS;
        }

        $this->repairDocumentSequences();
        $this->closeDuplicateOpenSessions();
        $this->backfillOrderItemCents();
        $this->ensureOpenFlagIndex();

        $this->info('✓ Reparación Conurbania finalizada.');

        return self::SUCCESS;
    }

    private function repairDocumentSequences(): void
    {
        if (! Schema::hasTable('document_sequences') || ! Schema::hasTable('orders')) {
            $this->warn('document_sequences u orders no existen; skip secuencias.');

            return;
        }

        $maxByTenantYear = [];
        foreach (DB::table('orders')->select('restaurant_id', 'number')->cursor() as $row) {
            if (! preg_match('/^ORD-(\d{4})-(\d+)$/', (string) $row->number, $m)) {
                continue;
            }
            $key = $row->restaurant_id.'|'.$m[1];
            $maxByTenantYear[$key] = max($maxByTenantYear[$key] ?? 0, (int) $m[2]);
        }

        if (Schema::hasTable('order_counters')) {
            foreach (DB::table('order_counters')->get() as $c) {
                $key = $c->restaurant_id.'|'.$c->year;
                $maxByTenantYear[$key] = max($maxByTenantYear[$key] ?? 0, (int) $c->last_seq);
            }
        }

        $n = 0;
        foreach ($maxByTenantYear as $key => $seq) {
            [$restaurantId, $year] = explode('|', $key, 2);
            $existing = (int) (DB::table('document_sequences')
                ->where('restaurant_id', $restaurantId)
                ->where('type', 'order')
                ->where('period', $year)
                ->value('next_value') ?? 0);

            $next = max($existing, $seq + 1);
            DB::table('document_sequences')->updateOrInsert(
                [
                    'restaurant_id' => (int) $restaurantId,
                    'type' => 'order',
                    'period' => (int) $year,
                ],
                [
                    'next_value' => $next,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $n++;
        }

        $this->line("  · secuencias alineadas: {$n} locales/años");
    }

    private function closeDuplicateOpenSessions(): void
    {
        if (! Schema::hasTable('table_sessions')) {
            return;
        }

        $dupes = DB::table('table_sessions')
            ->select('table_id', DB::raw('COUNT(*) as c'))
            ->where('status', 'ABIERTA')
            ->whereNotNull('table_id')
            ->groupBy('table_id')
            ->having('c', '>', 1)
            ->get();

        $closed = 0;
        foreach ($dupes as $d) {
            $keepId = DB::table('table_sessions')
                ->where('table_id', $d->table_id)
                ->where('status', 'ABIERTA')
                ->orderByDesc('id')
                ->value('id');

            $closed += DB::table('table_sessions')
                ->where('table_id', $d->table_id)
                ->where('status', 'ABIERTA')
                ->where('id', '!=', $keepId)
                ->update([
                    'status' => 'CERRADA',
                    'ended_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $this->line("  · sesiones abiertas duplicadas cerradas: {$closed}");
    }

    private function backfillOrderItemCents(): void
    {
        if (! Schema::hasColumn('order_items', 'unit_price_cents')) {
            return;
        }

        $updated = 0;
        DB::table('order_items')
            ->whereNull('unit_price_cents')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$updated) {
                foreach ($rows as $row) {
                    DB::table('order_items')->where('id', $row->id)->update([
                        'unit_price_cents' => (int) round(((float) $row->unit_price) * 100),
                        'line_total_cents' => (int) round(((float) $row->subtotal) * 100),
                    ]);
                    $updated++;
                }
            });

        $this->line("  · order_items cents backfill: {$updated}");
    }

    private function ensureOpenFlagIndex(): void
    {
        if (! Schema::hasTable('table_sessions')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        if (! Schema::hasColumn('table_sessions', 'open_flag')) {
            try {
                DB::statement("
                    ALTER TABLE table_sessions
                    ADD COLUMN open_flag TINYINT
                        GENERATED ALWAYS AS (CASE WHEN status = 'ABIERTA' THEN 1 ELSE NULL END) STORED
                ");
                $this->line('  · columna open_flag creada');
            } catch (\Throwable $e) {
                $this->warn('  · open_flag: '.$e->getMessage());
            }
        }

        try {
            DB::statement('
                ALTER TABLE table_sessions
                ADD UNIQUE KEY table_sessions_one_open_per_table (table_id, open_flag)
            ');
            $this->line('  · unique one_open_per_table OK');
        } catch (\Throwable $e) {
            // ya existe o aún hay duplicados
            $this->line('  · unique one_open_per_table: '.$e->getMessage());
        }
    }
}

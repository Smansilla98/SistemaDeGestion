<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1) Alinea order_counters con el máximo real de orders (fix desync ORD-*-NNNN).
 * 2) Pasa el unique de number de global a (restaurant_id, number) — multi-tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->backfillOrderCounters();

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['number']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['restaurant_id', 'number']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('number');
        });
    }

    private function backfillOrderCounters(): void
    {
        if (! Schema::hasTable('order_counters') || ! Schema::hasTable('orders')) {
            return;
        }

        $maxByTenantYear = [];

        foreach (DB::table('orders')->select('restaurant_id', 'number')->cursor() as $row) {
            if (! preg_match('/^ORD-(\d{4})-(\d+)$/', (string) $row->number, $m)) {
                continue;
            }

            $key = $row->restaurant_id.'|'.$m[1];
            $seq = (int) $m[2];
            $maxByTenantYear[$key] = max($maxByTenantYear[$key] ?? 0, $seq);
        }

        foreach ($maxByTenantYear as $key => $seq) {
            [$restaurantId, $year] = explode('|', $key, 2);

            $existing = DB::table('order_counters')
                ->where('restaurant_id', $restaurantId)
                ->where('year', $year)
                ->value('last_seq');

            $lastSeq = max((int) ($existing ?? 0), $seq);

            DB::table('order_counters')->updateOrInsert(
                [
                    'restaurant_id' => (int) $restaurantId,
                    'year' => (int) $year,
                ],
                [
                    'last_seq' => $lastSeq,
                ]
            );
        }
    }
};

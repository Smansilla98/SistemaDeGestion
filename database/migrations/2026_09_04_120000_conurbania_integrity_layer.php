<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Capas de integridad: secuencias, idempotencia, auditoría, índices, snapshots.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_sequences')) {
            Schema::create('document_sequences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
                $table->string('type', 32);
                $table->unsignedSmallInteger('period');
                $table->unsignedBigInteger('next_value')->default(1);
                $table->timestamps();
                $table->unique(['restaurant_id', 'type', 'period'], 'document_sequences_unique');
            });
        }

        $this->backfillDocumentSequences();

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'idempotency_key')) {
                $table->ulid('idempotency_key')->nullable()->after('id');
            }
            if (! Schema::hasColumn('orders', 'lock_version')) {
                $table->unsignedInteger('lock_version')->default(0)->after('status');
            }
            if (! Schema::hasColumn('orders', 'kitchen_printed_at')) {
                $table->timestamp('kitchen_printed_at')->nullable()->after('sent_at');
            }
        });

        // Unique idempotencia (nullable: múltiples null OK en MySQL)
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique(['restaurant_id', 'idempotency_key'], 'orders_idempotency_unique');
            });
        } catch (\Throwable) {
            // ya existe
        }

        // Unique por tenant (si la migración anterior no corrió)
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_number_unique');
            });
        } catch (\Throwable) {
        }
        try {
            Schema::table('orders', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
            });
        } catch (\Throwable) {
        }
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique(['restaurant_id', 'number'], 'orders_restaurant_number_unique');
            });
        } catch (\Throwable) {
        }

        Schema::table('orders', function (Blueprint $table) {
            try {
                $table->index(['restaurant_id', 'status', 'created_at'], 'orders_board_index');
            } catch (\Throwable) {
            }
            try {
                $table->index(['table_session_id', 'status'], 'orders_session_index');
            } catch (\Throwable) {
            }
            try {
                $table->index(['restaurant_id', 'created_at'], 'orders_reports_index');
            } catch (\Throwable) {
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'product_name_snapshot')) {
                $table->string('product_name_snapshot')->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('order_items', 'unit_price_cents')) {
                $table->unsignedBigInteger('unit_price_cents')->nullable()->after('unit_price');
            }
            if (! Schema::hasColumn('order_items', 'line_total_cents')) {
                $table->unsignedBigInteger('line_total_cents')->nullable()->after('subtotal');
            }
        });

        // Backfill snapshots desde unit_price decimal existente
        if (Schema::hasColumn('order_items', 'unit_price_cents')) {
            DB::table('order_items')
                ->whereNull('unit_price_cents')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $cents = (int) round(((float) $row->unit_price) * 100);
                        $line = (int) round(((float) $row->subtotal) * 100);
                        DB::table('order_items')->where('id', $row->id)->update([
                            'unit_price_cents' => $cents,
                            'line_total_cents' => $line,
                        ]);
                    }
                });
        }

        if (! Schema::hasTable('order_status_changes')) {
            Schema::create('order_status_changes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32);
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('reason')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->morphs('auditable');
                $table->string('event');
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('reason')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['auditable_type', 'auditable_id', 'created_at'], 'audit_logs_auditable_created');
            });
        }

        $this->addOneOpenSessionConstraint();
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('order_status_changes');
        Schema::dropIfExists('document_sequences');

        Schema::table('orders', function (Blueprint $table) {
            foreach (['orders_board_index', 'orders_session_index', 'orders_reports_index', 'orders_idempotency_unique'] as $idx) {
                try {
                    $table->dropIndex($idx);
                } catch (\Throwable) {
                }
            }
            foreach (['idempotency_key', 'lock_version', 'kitchen_printed_at'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function backfillDocumentSequences(): void
    {
        $maxByTenantYear = [];

        if (Schema::hasTable('orders')) {
            foreach (DB::table('orders')->select('restaurant_id', 'number')->cursor() as $row) {
                if (! preg_match('/^ORD-(\d{4})-(\d+)$/', (string) $row->number, $m)) {
                    continue;
                }
                $key = $row->restaurant_id.'|'.$m[1];
                $maxByTenantYear[$key] = max($maxByTenantYear[$key] ?? 0, (int) $m[2]);
            }
        }

        // Preferir order_counters si está más adelante
        if (Schema::hasTable('order_counters')) {
            foreach (DB::table('order_counters')->get() as $c) {
                $key = $c->restaurant_id.'|'.$c->year;
                $maxByTenantYear[$key] = max($maxByTenantYear[$key] ?? 0, (int) $c->last_seq);
            }
        }

        foreach ($maxByTenantYear as $key => $seq) {
            [$restaurantId, $year] = explode('|', $key, 2);
            DB::table('document_sequences')->updateOrInsert(
                [
                    'restaurant_id' => (int) $restaurantId,
                    'type' => 'order',
                    'period' => (int) $year,
                ],
                [
                    'next_value' => $seq + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function addOneOpenSessionConstraint(): void
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
            } catch (\Throwable) {
                return;
            }
        }

        try {
            DB::statement('
                ALTER TABLE table_sessions
                ADD UNIQUE KEY table_sessions_one_open_per_table (table_id, open_flag)
            ');
        } catch (\Throwable) {
            // puede fallar si ya hay dos abiertas: se documenta como deuda operativa
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Centavos como fuente de verdad en order_items y payments (dual-read compatible).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_items')) {
            if (! Schema::hasColumn('order_items', 'unit_price_cents')) {
                Schema::table('order_items', function (Blueprint $table) {
                    $table->unsignedBigInteger('unit_price_cents')->nullable()->after('unit_price');
                });
            }
            if (! Schema::hasColumn('order_items', 'line_total_cents')) {
                Schema::table('order_items', function (Blueprint $table) {
                    $table->unsignedBigInteger('line_total_cents')->nullable()->after('subtotal');
                });
            }
            if (! Schema::hasColumn('order_items', 'product_name_snapshot')) {
                Schema::table('order_items', function (Blueprint $table) {
                    $table->string('product_name_snapshot')->nullable()->after('product_id');
                });
            }

            DB::table('order_items')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $updates = [];
                    if ($row->unit_price_cents === null) {
                        $updates['unit_price_cents'] = (int) round(((float) $row->unit_price) * 100);
                    }
                    if ($row->line_total_cents === null) {
                        $updates['line_total_cents'] = (int) round(((float) $row->subtotal) * 100);
                    }
                    if ($updates) {
                        DB::table('order_items')->where('id', $row->id)->update($updates);
                    }
                }
            });

            // Fuente de verdad: centavos obligatorios en líneas.
            DB::table('order_items')->whereNull('unit_price_cents')->update(['unit_price_cents' => 0]);
            DB::table('order_items')->whereNull('line_total_cents')->update(['line_total_cents' => 0]);
            DB::statement('ALTER TABLE order_items MODIFY unit_price_cents BIGINT UNSIGNED NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE order_items MODIFY line_total_cents BIGINT UNSIGNED NOT NULL DEFAULT 0');
        }

        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'amount_cents')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('amount_cents')->nullable()->after('amount');
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'amount_cents')) {
            DB::table('payments')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    if ($row->amount_cents !== null) {
                        continue;
                    }
                    DB::table('payments')->where('id', $row->id)->update([
                        'amount_cents' => (int) round(((float) $row->amount) * 100),
                    ]);
                }
            });
            DB::table('payments')->whereNull('amount_cents')->update(['amount_cents' => 0]);
            DB::statement('ALTER TABLE payments MODIFY amount_cents BIGINT UNSIGNED NOT NULL DEFAULT 0');
        }

        if (Schema::hasTable('orders')) {
            if (! Schema::hasColumn('orders', 'subtotal_cents')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->unsignedBigInteger('subtotal_cents')->nullable()->after('subtotal');
                    $table->unsignedBigInteger('discount_cents')->nullable()->after('discount');
                    $table->unsignedBigInteger('total_cents')->nullable()->after('total');
                });
            }

            DB::table('orders')->orderBy('id')->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('orders')->where('id', $row->id)->update([
                        'subtotal_cents' => (int) round(((float) ($row->subtotal ?? 0)) * 100),
                        'discount_cents' => (int) round(((float) ($row->discount ?? 0)) * 100),
                        'total_cents' => (int) round(((float) ($row->total ?? 0)) * 100),
                    ]);
                }
            });
            DB::table('orders')->whereNull('subtotal_cents')->update(['subtotal_cents' => 0]);
            DB::table('orders')->whereNull('discount_cents')->update(['discount_cents' => 0]);
            DB::table('orders')->whereNull('total_cents')->update(['total_cents' => 0]);
            DB::statement('ALTER TABLE orders MODIFY subtotal_cents BIGINT UNSIGNED NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE orders MODIFY discount_cents BIGINT UNSIGNED NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE orders MODIFY total_cents BIGINT UNSIGNED NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                foreach (['unit_price_cents', 'line_total_cents', 'product_name_snapshot'] as $col) {
                    if (Schema::hasColumn('order_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'amount_cents')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('amount_cents');
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                foreach (['subtotal_cents', 'discount_cents', 'total_cents'] as $col) {
                    if (Schema::hasColumn('orders', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

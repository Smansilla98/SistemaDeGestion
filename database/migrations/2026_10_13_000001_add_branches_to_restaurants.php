<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sucursales: cada sucursal es un restaurante (tenant) propio.
     * parent_id apunta a la casa matriz; null = casa matriz o local único.
     * refresh_tokens.restaurant_id recuerda la sucursal elegida en la app.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('restaurants', 'parent_id')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->after('id')
                    ->constrained('restaurants')->nullOnDelete();
            });
        }

        if (Schema::hasTable('refresh_tokens') && ! Schema::hasColumn('refresh_tokens', 'restaurant_id')) {
            Schema::table('refresh_tokens', function (Blueprint $table) {
                $table->unsignedBigInteger('restaurant_id')->nullable()->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('refresh_tokens') && Schema::hasColumn('refresh_tokens', 'restaurant_id')) {
            Schema::table('refresh_tokens', function (Blueprint $table) {
                $table->dropColumn('restaurant_id');
            });
        }

        if (Schema::hasColumn('restaurants', 'parent_id')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parent_id');
            });
        }
    }
};

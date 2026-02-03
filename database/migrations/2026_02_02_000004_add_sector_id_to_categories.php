<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        // Verificar si la columna ya existe
        $columns = DB::select("SHOW COLUMNS FROM categories");
        $columnNames = array_column($columns, 'Field');

        if (!in_array('sector_id', $columnNames)) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreignId('sector_id')->nullable()->after('restaurant_id')->constrained('sectors')->onDelete('cascade');
                $table->index('sector_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('categories')) {
            $columns = DB::select("SHOW COLUMNS FROM categories");
            $columnNames = array_column($columns, 'Field');

            if (in_array('sector_id', $columnNames)) {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropForeign(['sector_id']);
                    $table->dropColumn('sector_id');
                });
            }
        }
    }
};


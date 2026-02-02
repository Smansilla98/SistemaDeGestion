<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('restaurant_id')->constrained('sectors')->onDelete('cascade');
            $table->string('type')->default('SECTOR')->after('parent_id'); // SECTOR o SUBSECTOR
            $table->integer('capacity')->nullable()->after('type'); // Capacidad total del subsector (ej: 4 para la barra)
            
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'type', 'capacity']);
        });
    }
};


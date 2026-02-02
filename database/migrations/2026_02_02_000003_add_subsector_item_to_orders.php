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
        Schema::table('orders', function (Blueprint $table) {
            // Hacer table_id nullable para permitir pedidos desde subsector items
            $table->foreignId('table_id')->nullable()->change();
            
            // Agregar subsector_item_id
            $table->foreignId('subsector_item_id')->nullable()->after('table_id')->constrained('subsector_items')->onDelete('cascade');
            
            $table->index('subsector_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['subsector_item_id']);
            $table->dropColumn('subsector_item_id');
            // No revertir table_id a not null para evitar problemas con datos existentes
        });
    }
};


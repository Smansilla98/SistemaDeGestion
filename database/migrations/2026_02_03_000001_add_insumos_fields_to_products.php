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
        Schema::table('products', function (Blueprint $table) {
            // Tipo de producto: PRODUCT (vendible) o INSUMO (no vendible)
            if (!Schema::hasColumn('products', 'type')) {
                $table->enum('type', ['PRODUCT', 'INSUMO'])->default('PRODUCT')->after('category_id');
            }
            
            // Campos específicos para insumos
            if (!Schema::hasColumn('products', 'unit')) {
                $table->string('unit')->nullable()->after('price'); // unidad, caja, paquete, kg, litro, etc.
            }
            
            if (!Schema::hasColumn('products', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 2)->nullable()->after('unit'); // Costo unitario para insumos
            }
            
            if (!Schema::hasColumn('products', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('unit_cost')
                    ->constrained('suppliers')->onDelete('set null');
            }
            
            // Hacer category_id nullable para insumos que no necesitan categoría
            // (esto requiere una migración separada si ya hay datos)
            
            // Índices
            $table->index('type');
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            }
            
            if (Schema::hasColumn('products', 'unit_cost')) {
                $table->dropColumn('unit_cost');
            }
            
            if (Schema::hasColumn('products', 'unit')) {
                $table->dropColumn('unit');
            }
            
            if (Schema::hasColumn('products', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};


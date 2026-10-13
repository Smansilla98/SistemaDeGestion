<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Variantes: un producto base (ej. Pizza) se vende eligiendo una variante
     * (ej. Napolitana) que define el precio. Los EXTRA siguen sumando un delta.
     *
     * Proporciones: un trago se arma como % de la medida del vaso
     * (ej. 500 cc → 30% fernet + 70% coca). quantity queda derivada del %.
     */
    public function up(): void
    {
        Schema::table('product_modifiers', function (Blueprint $table) {
            if (! Schema::hasColumn('product_modifiers', 'kind')) {
                $table->string('kind', 16)->default('EXTRA')->after('name'); // EXTRA | VARIANT
            }
            if (! Schema::hasColumn('product_modifiers', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('price_modifier'); // precio final de la variante
            }
            if (! Schema::hasColumn('product_modifiers', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            }
        });

        if (! Schema::hasTable('product_modifier_ingredients')) {
            Schema::create('product_modifier_ingredients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_modifier_id')->constrained('product_modifiers')->cascadeOnDelete();
                $table->foreignId('ingredient_id')->constrained('products')->cascadeOnDelete(); // insumo
                $table->unsignedInteger('quantity')->default(1); // en la unidad del insumo
                $table->timestamps();

                $table->unique(['product_modifier_id', 'ingredient_id'], 'pmi_modifier_ingredient_unique');
            });
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'serving_size')) {
                $table->unsignedInteger('serving_size')->nullable()->after('unit'); // medida del vaso (cc/ml)
            }
        });

        Schema::table('product_ingredients', function (Blueprint $table) {
            if (! Schema::hasColumn('product_ingredients', 'percentage')) {
                $table->decimal('percentage', 5, 2)->nullable()->after('quantity'); // % del vaso; null = cantidad fija
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_ingredients', function (Blueprint $table) {
            if (Schema::hasColumn('product_ingredients', 'percentage')) {
                $table->dropColumn('percentage');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'serving_size')) {
                $table->dropColumn('serving_size');
            }
        });

        Schema::dropIfExists('product_modifier_ingredients');

        Schema::table('product_modifiers', function (Blueprint $table) {
            foreach (['sort_order', 'price', 'kind'] as $column) {
                if (Schema::hasColumn('product_modifiers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

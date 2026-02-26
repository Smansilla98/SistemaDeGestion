<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Receta de productos: un producto vendible (ej. Fernet con coca)
     * se compone de N insumos (ej. Fernet Branca, Coca Cola) con cantidades.
     * Las cantidades están en la unidad del insumo (ej. ml).
     */
    public function up(): void
    {
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // producto vendible (PRODUCT)
            $table->foreignId('ingredient_id')->constrained('products')->onDelete('cascade'); // insumo (INSUMO)
            $table->unsignedInteger('quantity')->default(1); // cantidad por unidad de producto (ej. 60 ml)
            $table->string('unit')->nullable(); // opcional: override de unidad (ej. ml, L)
            $table->timestamps();

            $table->unique(['product_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
    }
};

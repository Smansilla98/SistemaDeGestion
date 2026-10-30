<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Oferta de cada ciclo del nodo: qué productos se pueden pedir, a qué precio y con qué cupo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_cycle_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_product_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('price_cents');
            $table->decimal('max_quantity', 12, 3)->nullable(); // cupo total del ciclo; null = sin tope
            $table->timestamps();
            $table->unique(['order_cycle_id', 'market_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_cycle_products');
    }
};

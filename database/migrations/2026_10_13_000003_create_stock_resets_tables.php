<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Puesta a punto: corte general del local + conteo real de stock.
     * Cada ejecución queda registrada con quién la hizo y qué se bajó.
     */
    public function up(): void
    {
        Schema::create('stock_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedInteger('orders_cancelled')->default(0);
            $table->bigInteger('orders_cancelled_total_cents')->default(0);
            $table->unsignedInteger('sessions_closed')->default(0);
            $table->unsignedInteger('tables_freed')->default(0);
            $table->unsignedInteger('products_adjusted')->default(0);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'created_at']);
        });

        Schema::create('stock_reset_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_reset_id')->constrained('stock_resets')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('previous_quantity');
            $table->integer('counted_quantity');
            $table->timestamps();

            $table->unique(['stock_reset_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reset_items');
        Schema::dropIfExists('stock_resets');
    }
};

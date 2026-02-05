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
        Schema::create('event_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('expected_quantity')->default(0); // Cantidad esperada para el evento
            $table->integer('actual_quantity')->nullable(); // Cantidad real usada (se completa después)
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['event_id', 'product_id']);
            $table->index('event_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_products');
    }
};


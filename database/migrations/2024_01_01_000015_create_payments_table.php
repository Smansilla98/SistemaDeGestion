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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('cash_register_session_id')->nullable()->constrained('cash_register_sessions')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Usuario que procesa el pago
            $table->enum('payment_method', ['EFECTIVO', 'DEBITO', 'CREDITO', 'TRANSFERENCIA']);
            $table->decimal('amount', 10, 2);
            $table->string('reference')->nullable(); // Referencia de pago (ej: número de tarjeta parcial)
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['restaurant_id', 'order_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};



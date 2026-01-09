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
        Schema::create('cash_register_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('cash_register_id')->constrained('cash_registers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Usuario que abre/cierra
            $table->decimal('initial_amount', 10, 2); // Monto inicial
            $table->decimal('final_amount', 10, 2)->nullable(); // Monto final calculado
            $table->decimal('expected_amount', 10, 2)->nullable(); // Monto esperado según ventas
            $table->decimal('difference', 10, 2)->nullable(); // Diferencia entre final y esperado
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['ABIERTA', 'CERRADA'])->default('ABIERTA');
            $table->text('notes')->nullable(); // Notas del cierre
            $table->timestamps();
            
            $table->index(['restaurant_id', 'cash_register_id']);
            $table->index('status');
            $table->index('opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_sessions');
    }
};



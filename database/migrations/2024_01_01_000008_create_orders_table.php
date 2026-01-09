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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('table_id')->constrained('tables')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Mozo que toma el pedido
            $table->string('number')->unique(); // Número de pedido: "ORD-2024-001"
            $table->enum('status', ['ABIERTO', 'ENVIADO', 'EN_PREPARACION', 'LISTO', 'ENTREGADO', 'CERRADO', 'CANCELADO'])->default('ABIERTO');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('observations')->nullable(); // Observaciones generales del pedido
            $table->timestamp('sent_at')->nullable(); // Cuando se envió a cocina
            $table->timestamp('closed_at')->nullable(); // Cuando se cerró el pedido
            $table->timestamps();
            
            $table->index(['restaurant_id', 'table_id']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};



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
        Schema::create('subsector_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subsector_id')->constrained('sectors')->onDelete('cascade');
            $table->string('name'); // Nombre del elemento (ej: "Lugar 1", "Barra 1")
            $table->integer('position')->default(0); // Posición/orden dentro del subsector
            $table->string('status')->default('LIBRE'); // LIBRE, OCUPADA, RESERVADA, CERRADA
            $table->foreignId('current_order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('current_session_id')->nullable()->constrained('table_sessions')->onDelete('set null');
            $table->timestamps();
            
            $table->index('subsector_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsector_items');
    }
};


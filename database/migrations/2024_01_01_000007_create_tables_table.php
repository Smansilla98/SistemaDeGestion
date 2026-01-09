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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('sector_id')->constrained('sectors')->onDelete('cascade');
            $table->string('number'); // Número de mesa: "Mesa 1", "1", "A1"
            $table->integer('capacity'); // Capacidad de personas
            $table->integer('position_x')->nullable(); // Posición X en el layout
            $table->integer('position_y')->nullable(); // Posición Y en el layout
            $table->enum('status', ['LIBRE', 'OCUPADA', 'RESERVADA', 'CERRADA'])->default('LIBRE');
            $table->unsignedBigInteger('current_order_id')->nullable();
            $table->timestamps();
            
            $table->index(['restaurant_id', 'sector_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};


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
        Schema::create('recurring_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->string('name'); // Ej: Tango, Clase de Yoga
            $table->text('description')->nullable();
            $table->enum('day_of_week', ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY']);
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->integer('expected_attendance')->nullable(); // Cantidad de gente esperada
            $table->decimal('expected_revenue', 10, 2)->nullable(); // Ingreso económico esperado
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable(); // Fecha de inicio
            $table->date('end_date')->nullable(); // Fecha de fin (null = indefinido)
            $table->timestamps();
            
            $table->index(['restaurant_id', 'day_of_week', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_activities');
    }
};


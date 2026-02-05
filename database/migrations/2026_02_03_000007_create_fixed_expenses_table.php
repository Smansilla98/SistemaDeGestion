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
        Schema::create('fixed_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->string('name'); // Ej: Alquiler, Luz, Agua
            $table->text('description')->nullable();
            $table->enum('type', ['GASTO', 'INGRESO'])->default('GASTO');
            $table->enum('category', [
                'ALQUILER',
                'SERVICIOS', // Luz, Agua, Gas, Internet
                'PERSONAL', // Mozo, Limpieza
                'OPERATIVOS', // Rotura de vajillas, mantenimiento
                'TALLER', // Ingresos por talleres/actividades
                'OTROS'
            ])->default('OTROS');
            $table->decimal('amount', 10, 2); // Monto fijo
            $table->enum('frequency', ['MENSUAL', 'QUINCENAL', 'SEMANAL', 'DIARIO', 'ANUAL'])->default('MENSUAL');
            $table->date('start_date'); // Fecha de inicio
            $table->date('end_date')->nullable(); // Fecha de fin (null = indefinido)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['restaurant_id', 'is_active']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_expenses');
    }
};


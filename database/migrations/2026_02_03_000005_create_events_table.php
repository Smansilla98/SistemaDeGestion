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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('date');
            $table->time('time')->nullable();
            $table->integer('expected_attendance')->nullable(); // Asistencia esperada
            $table->enum('status', ['PROGRAMADO', 'EN_CURSO', 'FINALIZADO', 'CANCELADO'])->default('PROGRAMADO');
            $table->timestamps();
            
            $table->index(['restaurant_id', 'date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};


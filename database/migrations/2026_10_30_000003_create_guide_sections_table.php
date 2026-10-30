<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guía interna de comunicación: qué publicar, cómo, cuándo y con qué formato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('slug', 80);
            $table->string('title');
            $table->string('summary', 300)->nullable();
            $table->string('audience', 20)->default('TODOS');
            $table->string('icon', 40)->default('bi-megaphone');
            $table->text('body');
            $table->string('template_id', 60)->nullable(); // plantilla del editor de Diseño para arrancar
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['restaurant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_sections');
    }
};

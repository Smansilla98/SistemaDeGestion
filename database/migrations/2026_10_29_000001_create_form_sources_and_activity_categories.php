<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorías de actividades (Música, Teatro, Feria…) para talleres, eventos y avisos de agenda,
     * y fuentes de formularios (Google Forms / planillas) que alimentan alumnos, talleristas,
     * contactos y agenda. Cada respuesta importada se guarda con un hash para no repetirla.
     */
    public function up(): void
    {
        Schema::create('activity_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('color', 7)->default('#6c757d');
            $table->timestamps();
            $table->unique(['restaurant_id', 'name']);
        });

        foreach (['workshops', 'events', 'agenda_notices'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->foreignId('activity_category_id')->nullable()->constrained('activity_categories')->nullOnDelete();
            });
        }

        Schema::create('form_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name');
            $table->string('target', 20); // ALUMNOS | TALLERISTAS | CONTACTOS | AGENDA
            $table->string('sheet_url', 500)->nullable();
            $table->json('mapping')->nullable(); // campo del sistema => encabezado de la planilla
            $table->json('headers')->nullable();
            $table->foreignId('workshop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('activity_category_id')->nullable()->constrained('activity_categories')->nullOnDelete();
            $table->string('contact_kind', 20)->nullable();
            $table->string('tags')->nullable();
            $table->boolean('auto_sync')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('form_source_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_source_id')->constrained()->cascadeOnDelete();
            $table->char('row_hash', 40);
            $table->json('data');
            $table->string('status', 15); // IMPORTADA | ERROR | OMITIDA
            $table->string('message', 500)->nullable();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['form_source_id', 'row_hash']);
            $table->index(['form_source_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_source_rows');
        Schema::dropIfExists('form_sources');
        foreach (['agenda_notices', 'events', 'workshops'] as $t) {
            Schema::table($t, fn (Blueprint $table) => $table->dropConstrainedForeignId('activity_category_id'));
        }
        Schema::dropIfExists('activity_categories');
    }
};

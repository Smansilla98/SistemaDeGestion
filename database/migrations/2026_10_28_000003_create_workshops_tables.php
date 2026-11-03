<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Talleres: horarios semanales, clases materializadas (cada una puede reprogramarse o
     * cancelarse sin perder el historial), inscripciones con cupo y lista de espera,
     * asistencia y evaluaciones. Alumnos y profesores son contactos (cuenta opcional).
     */
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('teacher_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('room')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedBigInteger('fee_cents')->default(0);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 15)->default('ABIERTO');
            $table->timestamps();
            $table->index(['restaurant_id', 'status']);
        });

        Schema::create('workshop_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 1 = lunes … 7 = domingo (ISO)
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        Schema::create('workshop_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workshop_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 15)->default('PROGRAMADA');
            $table->date('original_date')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['workshop_id', 'date', 'start_time']);
            $table->index('date');
        });

        Schema::create('workshop_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->string('status', 15)->default('ACTIVA');
            $table->unsignedInteger('waitlist_position')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['workshop_id', 'contact_id']);
            $table->index(['workshop_id', 'status']);
        });

        Schema::create('workshop_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->string('status', 15);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['workshop_session_id', 'contact_id']);
        });

        Schema::create('workshop_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('grade', 30)->nullable();
            $table->text('comment')->nullable();
            $table->date('evaluated_on');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('agenda_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('kind', 20)->default('AVISO');
            $table->string('title');
            $table->text('body')->nullable();
            $table->date('date');
            $table->date('end_date')->nullable();
            $table->time('time')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['restaurant_id', 'date']);
        });
    }

    public function down(): void
    {
        foreach (['agenda_notices', 'workshop_evaluations', 'workshop_attendances', 'workshop_enrollments',
            'workshop_sessions', 'workshop_schedules', 'workshops'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

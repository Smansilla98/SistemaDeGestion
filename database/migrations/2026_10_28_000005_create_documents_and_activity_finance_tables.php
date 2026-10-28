<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documentos con versiones (archivos en disco privado, descarga autorizada en el servidor),
     * y finanzas por actividad: reglas de reparto configurables (ej. 70/30), libro de ingresos
     * y gastos por taller o evento (se anula, no se borra), pagos de repartos y etapas de eventos.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('entrepreneur_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 20)->default('OTRO');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('visibility', 15)->default('GESTION');
            $table->string('status', 15)->default('BORRADOR');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['restaurant_id', 'status']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('note')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['document_id', 'version']);
        });

        Schema::create('distribution_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name');
            $table->string('base', 10)->default('NETO'); // BRUTO | NETO (ingresos − gastos reconocidos)
            $table->json('parties'); // [{label, percent}] suma 100
            $table->timestamps();
        });

        Schema::table('workshops', function (Blueprint $table) {
            $table->foreignId('distribution_rule_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('distribution_rule_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });

        Schema::create('event_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->foreignId('responsible_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->unsignedBigInteger('budget_cents')->default(0);
            $table->string('status', 15)->default('PENDIENTE');
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->morphs('entryable'); // Workshop | Event
            $table->string('type', 10); // INGRESO | GASTO
            $table->string('concept');
            $table->unsignedBigInteger('amount_cents');
            $table->date('date');
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workshop_enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 30)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->timestamps();
            $table->index(['restaurant_id', 'date']);
        });

        Schema::create('distribution_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->morphs('payoutable'); // Workshop | Event
            $table->string('party', 60);
            $table->unsignedBigInteger('amount_cents');
            $table->date('paid_on');
            $table->string('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_payouts');
        Schema::dropIfExists('activity_entries');
        Schema::dropIfExists('event_stages');
        Schema::table('events', fn (Blueprint $t) => $t->dropConstrainedForeignId('distribution_rule_id'));
        Schema::table('workshops', fn (Blueprint $t) => $t->dropConstrainedForeignId('distribution_rule_id'));
        Schema::dropIfExists('distribution_rules');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
    }
};

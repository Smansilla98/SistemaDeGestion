<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuración de medios de cobro por restaurante (alias/CVU/CBU de
 * transferencia, imagen de QR estático, etc.). No reemplaza `payments`:
 * un Payment sigue registrando CON QUÉ método se cobró (EFECTIVO, QR...);
 * esta tabla define CÓMO se cobra con cada método en este restaurante
 * (a qué alias transferir, qué QR mostrar). MIXTO no es un tipo acá — ver
 * Payment::METHOD_* / el enum de payments.payment_method — porque un cobro
 * mixto ya se registra como varias filas de Payment con método real cada
 * una, no como un medio de cobro configurable en sí mismo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_method_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->string('type', 20); // EFECTIVO | DEBITO | CREDITO | TRANSFERENCIA | QR
            $table->string('label');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Transferencia
            $table->string('alias')->nullable();
            $table->string('cvu', 30)->nullable();
            $table->string('cbu', 30)->nullable();
            $table->string('account_holder')->nullable();
            $table->string('cuit', 15)->nullable();

            // QR estático
            $table->string('qr_image_path')->nullable();

            $table->text('instructions')->nullable();

            // Preparado para un proveedor externo de QR dinámico (Fase 6 del
            // pedido original). Sin integración real hoy: ningún código
            // escribe estas dos columnas todavía, quedan null.
            $table->string('provider')->nullable();
            $table->string('external_reference')->nullable();

            $table->timestamps();

            $table->unique(['restaurant_id', 'type']);
            $table->index(['restaurant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_method_configurations');
    }
};

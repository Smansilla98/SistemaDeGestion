<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar campos de pago y caja a table_sessions
     * Para implementar flujo completo de cierre de mesa con pago
     */
    public function up(): void
    {
        Schema::table('table_sessions', function (Blueprint $table) {
            // Campos de pago
            $table->decimal('total_amount', 10, 2)->nullable()->after('status');
            $table->timestamp('paid_at')->nullable()->after('total_amount');
            $table->enum('payment_method', ['EFECTIVO', 'DEBITO', 'CREDITO', 'TRANSFERENCIA', 'QR', 'MIXTO'])->nullable()->after('paid_at');
            
            // Relación con caja
            $table->foreignId('cash_register_id')->nullable()->after('payment_method')->constrained('cash_registers')->onDelete('set null');
            
            // Agregar índices
            $table->index('paid_at');
            $table->index('cash_register_id');
        });
        
        // Cambiar valores de status de OPEN/CLOSED a ABIERTA/CERRADA
        DB::statement("UPDATE table_sessions SET status = CASE WHEN status = 'OPEN' THEN 'ABIERTA' WHEN status = 'CLOSED' THEN 'CERRADA' ELSE 'ABIERTA' END");
        
        // Modificar el enum de status
        DB::statement("ALTER TABLE table_sessions MODIFY COLUMN status ENUM('ABIERTA', 'CERRADA') DEFAULT 'ABIERTA'");
    }

    public function down(): void
    {
        // Revertir status
        DB::statement("UPDATE table_sessions SET status = CASE WHEN status = 'ABIERTA' THEN 'OPEN' WHEN status = 'CERRADA' THEN 'CLOSED' ELSE 'OPEN' END");
        DB::statement("ALTER TABLE table_sessions MODIFY COLUMN status ENUM('OPEN', 'CLOSED') DEFAULT 'OPEN'");
        
        Schema::table('table_sessions', function (Blueprint $table) {
            $table->dropForeign(['cash_register_id']);
            $table->dropIndex(['cash_register_id']);
            $table->dropIndex(['paid_at']);
            $table->dropColumn(['total_amount', 'paid_at', 'payment_method', 'cash_register_id']);
        });
    }
};


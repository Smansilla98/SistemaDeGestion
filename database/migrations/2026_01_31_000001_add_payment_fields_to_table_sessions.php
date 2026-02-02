<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agregar campos de pago y caja a table_sessions
     * Para implementar flujo completo de cierre de mesa con pago
     */
    public function up(): void
    {
        if (!Schema::hasTable('table_sessions')) {
            return;
        }

        // Verificar y agregar columnas solo si no existen
        $columns = DB::select("SHOW COLUMNS FROM table_sessions");
        $columnNames = array_column($columns, 'Field');

        // Agregar total_amount si no existe
        if (!in_array('total_amount', $columnNames)) {
            Schema::table('table_sessions', function (Blueprint $table) {
                $table->decimal('total_amount', 10, 2)->nullable()->after('status');
            });
        }
        
        // Agregar paid_at si no existe
        if (!in_array('paid_at', $columnNames)) {
            Schema::table('table_sessions', function (Blueprint $table) {
                $table->timestamp('paid_at')->nullable()->after('total_amount');
            });
        }
        
        // Agregar payment_method si no existe
        if (!in_array('payment_method', $columnNames)) {
            Schema::table('table_sessions', function (Blueprint $table) {
                $table->enum('payment_method', ['EFECTIVO', 'DEBITO', 'CREDITO', 'TRANSFERENCIA', 'QR', 'MIXTO'])->nullable()->after('paid_at');
            });
        }
        
        // Agregar cash_register_id si no existe
        if (!in_array('cash_register_id', $columnNames)) {
            Schema::table('table_sessions', function (Blueprint $table) {
                // Verificar si la tabla cash_registers existe antes de crear la foreign key
                if (Schema::hasTable('cash_registers')) {
                    $table->foreignId('cash_register_id')->nullable()->after('payment_method')->constrained('cash_registers')->onDelete('set null');
                } else {
                    $table->unsignedBigInteger('cash_register_id')->nullable()->after('payment_method');
                }
            });
        }
        
        // Agregar índices solo si no existen
        $indexes = DB::select("SHOW INDEXES FROM table_sessions");
        $indexNames = array_column($indexes, 'Key_name');
        
        // Actualizar columnNames después de posibles cambios
        $columns = DB::select("SHOW COLUMNS FROM table_sessions");
        $columnNames = array_column($columns, 'Field');
        
        if (!in_array('table_sessions_paid_at_index', $indexNames) && in_array('paid_at', $columnNames)) {
            try {
                DB::statement("CREATE INDEX table_sessions_paid_at_index ON table_sessions(paid_at)");
            } catch (\Exception $e) {
                // El índice ya existe o hay otro problema, continuar
            }
        }
        
        if (!in_array('table_sessions_cash_register_id_index', $indexNames) && in_array('cash_register_id', $columnNames)) {
            try {
                DB::statement("CREATE INDEX table_sessions_cash_register_id_index ON table_sessions(cash_register_id)");
            } catch (\Exception $e) {
                // El índice ya existe o hay otro problema, continuar
            }
        }
        
        // Cambiar valores de status de OPEN/CLOSED a ABIERTA/CERRADA (solo si es necesario)
        if (in_array('status', $columnNames)) {
            try {
                $statusColumn = DB::select("SHOW COLUMNS FROM table_sessions WHERE Field = 'status'");
                if (!empty($statusColumn)) {
                    $currentType = $statusColumn[0]->Type;
                    // Solo actualizar si el enum contiene OPEN o CLOSED
                    if (strpos($currentType, 'OPEN') !== false || strpos($currentType, 'CLOSED') !== false) {
                        DB::statement("UPDATE table_sessions SET status = CASE WHEN status = 'OPEN' THEN 'ABIERTA' WHEN status = 'CLOSED' THEN 'CERRADA' ELSE 'ABIERTA' END");
                        DB::statement("ALTER TABLE table_sessions MODIFY COLUMN status ENUM('ABIERTA', 'CERRADA') DEFAULT 'ABIERTA'");
                    }
                }
            } catch (\Exception $e) {
                // El enum ya está correcto o hay otro problema, continuar
            }
        }
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


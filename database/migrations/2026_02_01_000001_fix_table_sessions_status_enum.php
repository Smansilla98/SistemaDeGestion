<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corregir el enum de status en table_sessions
     * Asegura que el enum tenga los valores correctos: ABIERTA, CERRADA
     */
    public function up(): void
    {
        if (!Schema::hasTable('table_sessions')) {
            return;
        }

        // Verificar si la columna existe
        $columnExists = DB::select("SHOW COLUMNS FROM table_sessions LIKE 'status'");
        if (empty($columnExists)) {
            return;
        }

        // Obtener el tipo actual de la columna
        $columnInfo = DB::select("SHOW COLUMNS FROM table_sessions WHERE Field = 'status'");
        if (empty($columnInfo)) {
            return;
        }

        $currentType = $columnInfo[0]->Type;
        
        // Si el enum no contiene ABIERTA y CERRADA, actualizarlo
        if (strpos($currentType, 'ABIERTA') === false || strpos($currentType, 'CERRADA') === false) {
            // Primero actualizar los valores existentes
            DB::statement("UPDATE table_sessions SET status = CASE 
                WHEN status = 'OPEN' THEN 'ABIERTA' 
                WHEN status = 'CLOSED' THEN 'CERRADA' 
                WHEN status = 'ABIERTA' THEN 'ABIERTA'
                WHEN status = 'CERRADA' THEN 'CERRADA'
                ELSE 'ABIERTA' 
            END");
            
            // Luego modificar el enum
            DB::statement("ALTER TABLE table_sessions MODIFY COLUMN status ENUM('ABIERTA', 'CERRADA') DEFAULT 'ABIERTA'");
        }
    }

    public function down(): void
    {
        // Revertir a OPEN/CLOSED si es necesario
        if (Schema::hasTable('table_sessions')) {
            DB::statement("UPDATE table_sessions SET status = CASE 
                WHEN status = 'ABIERTA' THEN 'OPEN' 
                WHEN status = 'CERRADA' THEN 'CLOSED' 
                ELSE 'OPEN' 
            END");
            DB::statement("ALTER TABLE table_sessions MODIFY COLUMN status ENUM('OPEN', 'CLOSED') DEFAULT 'OPEN'");
        }
    }
};


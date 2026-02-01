<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixTableSessionsEnum extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:table-sessions-enum';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige el enum de status en table_sessions si es necesario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando enum de status en table_sessions...');

        if (!Schema::hasTable('table_sessions')) {
            $this->warn('La tabla table_sessions no existe. Se creará con las migraciones.');
            return 0;
        }

        try {
            // Verificar el tipo actual de la columna
            $columnInfo = DB::select("SHOW COLUMNS FROM table_sessions WHERE Field = 'status'");
            
            if (empty($columnInfo)) {
                $this->warn('La columna status no existe en table_sessions.');
                return 0;
            }

            $currentType = $columnInfo[0]->Type;
            $this->info("Tipo actual de status: {$currentType}");

            // Verificar si el enum contiene ABIERTA y CERRADA
            if (strpos($currentType, 'ABIERTA') !== false && strpos($currentType, 'CERRADA') !== false) {
                $this->info('✓ El enum ya está correcto (ABIERTA, CERRADA)');
                return 0;
            }

            $this->warn('El enum necesita corrección. Actualizando...');

            // Actualizar valores existentes
            $updated = DB::statement("UPDATE table_sessions SET status = CASE 
                WHEN status = 'OPEN' THEN 'ABIERTA' 
                WHEN status = 'CLOSED' THEN 'CERRADA' 
                WHEN status = 'ABIERTA' THEN 'ABIERTA'
                WHEN status = 'CERRADA' THEN 'CERRADA'
                ELSE 'ABIERTA' 
            END");

            $this->info("Valores actualizados: " . ($updated ? 'Sí' : 'No'));

            // Modificar el enum
            DB::statement("ALTER TABLE table_sessions MODIFY COLUMN status ENUM('ABIERTA', 'CERRADA') DEFAULT 'ABIERTA'");

            $this->info('✓ Enum corregido exitosamente a: ENUM(\'ABIERTA\', \'CERRADA\')');

            // Verificar el resultado
            $newColumnInfo = DB::select("SHOW COLUMNS FROM table_sessions WHERE Field = 'status'");
            if (!empty($newColumnInfo)) {
                $this->info("Nuevo tipo: {$newColumnInfo[0]->Type}");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error al corregir el enum: ' . $e->getMessage());
            return 1;
        }
    }
}


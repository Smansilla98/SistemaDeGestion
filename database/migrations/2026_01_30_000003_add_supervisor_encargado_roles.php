<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Actualizar el enum de roles en la tabla users
        // MySQL requiere recrear la columna para cambiar el enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN', 'CAJERO', 'MOZO', 'COCINA', 'SUPERVISOR', 'ENCARGADO') DEFAULT 'MOZO'");
    }

    public function down(): void
    {
        // Revertir a los roles originales
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN', 'CAJERO', 'MOZO', 'COCINA') DEFAULT 'MOZO'");
    }
};


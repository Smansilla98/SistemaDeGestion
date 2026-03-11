<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN', 'GERENTE', 'CAJERO', 'MOZO', 'COCINA', 'SUPERVISOR', 'ENCARGADO') DEFAULT 'MOZO'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN', 'CAJERO', 'MOZO', 'COCINA', 'SUPERVISOR', 'ENCARGADO') DEFAULT 'MOZO'");
    }
};

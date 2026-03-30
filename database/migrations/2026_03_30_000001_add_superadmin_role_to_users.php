<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('SUPERADMIN', 'ADMIN', 'GERENTE', 'CAJERO', 'MOZO', 'COCINA', 'SUPERVISOR', 'ENCARGADO') DEFAULT 'MOZO'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE users SET role = 'ADMIN' WHERE role = 'SUPERADMIN'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN', 'GERENTE', 'CAJERO', 'MOZO', 'COCINA', 'SUPERVISOR', 'ENCARGADO') DEFAULT 'MOZO'");
    }
};

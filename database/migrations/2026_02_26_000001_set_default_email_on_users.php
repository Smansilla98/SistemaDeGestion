<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Establece email por defecto 'email@email.com' para usuarios creados sin email (login por username).
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE users DROP INDEX users_email_unique');
            } catch (\Throwable $e) {
                // Puede no existir si ya se eliminó
            }
            DB::statement("ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL DEFAULT 'email@email.com'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE users ADD UNIQUE users_email_unique (email)');
        }
    }
};

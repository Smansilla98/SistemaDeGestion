<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los roles de comunidad (coordinación, profe, alumno, etc.) pasan a poder ser el rol propio de la cuenta,
 * no sólo de una membresía: quien se registra en el espacio elige su rol.
 */
return new class extends Migration
{
    private const BASE = ['SUPERADMIN', 'ADMIN', 'GERENTE', 'CAJERO', 'MOZO', 'COCINA', 'SUPERVISOR', 'ENCARGADO'];

    private const COMMUNITY = ['COORDINADOR', 'PROFESOR', 'ALUMNO', 'EMPRENDEDOR', 'ARTISTA', 'MARKETING', 'INVENTARIO'];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->setEnum(array_merge(self::BASE, self::COMMUNITY));
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Las cuentas con roles nuevos quedan desactivadas como mozo para no perderlas.
        DB::table('users')->whereIn('role', self::COMMUNITY)->update(['role' => 'MOZO', 'is_active' => false]);
        $this->setEnum(self::BASE);
    }

    /** @param  list<string>  $roles */
    private function setEnum(array $roles): void
    {
        $list = implode(', ', array_map(fn ($r) => "'{$r}'", $roles));
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM({$list}) DEFAULT 'MOZO'");
    }
};

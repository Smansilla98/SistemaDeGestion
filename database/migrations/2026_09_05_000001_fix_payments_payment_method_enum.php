<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El enum de payments.payment_method quedó con los 4 métodos originales
 * (EFECTIVO, DEBITO, CREDITO, TRANSFERENCIA) mientras que la app valida y
 * envía además QR y MIXTO. Al cobrar con esos métodos MySQL rechaza el INSERT
 * ("Data truncated for column 'payment_method'") y la mesa nunca se cierra.
 */
return new class extends Migration
{
    private const METHODS = ['EFECTIVO', 'DEBITO', 'CREDITO', 'TRANSFERENCIA', 'QR', 'MIXTO'];

    public function up(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'payment_method')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            // SQLite/Postgres no usan enum nativo en este esquema: no hay nada que ajustar.
            return;
        }

        $values = implode(',', array_map(fn ($m) => "'".$m."'", self::METHODS));
        DB::statement("ALTER TABLE payments MODIFY payment_method ENUM({$values}) NOT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'payment_method')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // Los pagos que usan los métodos nuevos se normalizan para no perder el registro.
        DB::table('payments')->where('payment_method', 'QR')->update(['payment_method' => 'TRANSFERENCIA']);
        DB::table('payments')->where('payment_method', 'MIXTO')->update(['payment_method' => 'EFECTIVO']);

        DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('EFECTIVO','DEBITO','CREDITO','TRANSFERENCIA') NOT NULL");
    }
};

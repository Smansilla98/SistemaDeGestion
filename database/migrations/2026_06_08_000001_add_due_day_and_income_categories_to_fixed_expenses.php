<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_expenses', function (Blueprint $table) {
            $table->unsignedTinyInteger('due_day')->nullable()->after('frequency');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE fixed_expenses MODIFY COLUMN category ENUM(
                'ALQUILER','SERVICIOS','PERSONAL','OPERATIVOS','TALLER',
                'CANON','SUBSIDIO','CONTRATO','OTROS'
            ) NOT NULL DEFAULT 'OTROS'");
        }
    }

    public function down(): void
    {
        Schema::table('fixed_expenses', function (Blueprint $table) {
            $table->dropColumn('due_day');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE fixed_expenses MODIFY COLUMN category ENUM(
                'ALQUILER','SERVICIOS','PERSONAL','OPERATIVOS','TALLER','OTROS'
            ) NOT NULL DEFAULT 'OTROS'");
        }
    }
};

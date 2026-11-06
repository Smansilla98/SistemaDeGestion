<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Conurbania: la entrada de demo cargaba un evento de ejemplo («Peña de primavera (ejemplo)»)
 * que quedaba visible en Eventos. Se borra junto con sus etapas y movimientos.
 * Los demás datos de ejemplo pertenecen a módulos de centro cultural, que acá no existen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }

        $ids = DB::table('events')->where('name', 'Peña de primavera (ejemplo)')->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('activity_entries')) {
            DB::table('activity_entries')->whereIn('entryable_type', ['event', 'App\\Models\\Event'])->whereIn('entryable_id', $ids)->delete();
        }
        if (Schema::hasTable('distribution_payouts')) {
            DB::table('distribution_payouts')->whereIn('payoutable_type', ['event', 'App\\Models\\Event'])->whereIn('payoutable_id', $ids)->delete();
        }
        if (Schema::hasTable('event_stages')) {
            DB::table('event_stages')->whereIn('event_id', $ids)->delete();
        }
        DB::table('events')->whereIn('id', $ids)->delete();
    }

    public function down(): void
    {
        // Datos de ejemplo: no se restauran.
    }
};

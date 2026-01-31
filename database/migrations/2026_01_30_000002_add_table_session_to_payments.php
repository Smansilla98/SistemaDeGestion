<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('table_session_id')->nullable()->after('order_id')->constrained('table_sessions')->onDelete('set null');
            $table->string('operation_number')->nullable()->after('reference'); // Número de operación (opcional)
            $table->index('table_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['table_session_id']);
            $table->dropColumn(['table_session_id', 'operation_number']);
        });
    }
};


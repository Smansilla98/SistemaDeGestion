<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('table_sessions', function (Blueprint $table) {
            $table->foreignId('waiter_id')->nullable()->after('table_id')->constrained('users')->onDelete('set null');
            $table->foreignId('opened_by_user_id')->nullable()->after('waiter_id')->constrained('users')->onDelete('set null');
            $table->enum('status', ['ABIERTA', 'CERRADA'])->default('ABIERTA')->after('ended_at');
            
            $table->index('waiter_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('table_sessions', function (Blueprint $table) {
            $table->dropForeign(['waiter_id']);
            $table->dropForeign(['opened_by_user_id']);
            $table->dropColumn(['waiter_id', 'opened_by_user_id', 'status']);
        });
    }
};


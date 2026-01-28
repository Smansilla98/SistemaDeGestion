<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->unsignedBigInteger('current_session_id')->nullable()->after('current_order_id');
            $table->index('current_session_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('table_session_id')->nullable()->after('table_id');
            $table->index(['table_id', 'table_session_id']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['table_id', 'table_session_id']);
            $table->dropColumn('table_session_id');
        });

        Schema::table('tables', function (Blueprint $table) {
            $table->dropIndex(['current_session_id']);
            $table->dropColumn('current_session_id');
        });
    }
};



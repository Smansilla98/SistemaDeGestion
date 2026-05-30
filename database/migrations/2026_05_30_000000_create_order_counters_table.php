<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_counters', function (Blueprint $table) {
            $table->integer('restaurant_id');
            $table->smallInteger('year');
            $table->integer('last_seq')->default(0);
            $table->primary(['restaurant_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_counters');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('printers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['kitchen', 'bar', 'cashier', 'invoice'])->default('kitchen');
            $table->enum('driver', ['network', 'usb', 'file'])->default('network');
            $table->enum('connection_type', ['network', 'usb', 'file'])->default('network');
            $table->string('ip_address')->nullable();
            $table->integer('port')->default(9100);
            $table->string('path')->nullable(); // Para impresoras de archivo
            $table->boolean('is_active')->default(true);
            $table->integer('paper_width')->default(80); // 58mm o 80mm
            $table->boolean('auto_print')->default(false);
            $table->timestamps();

            $table->index(['restaurant_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printers');
    }
};


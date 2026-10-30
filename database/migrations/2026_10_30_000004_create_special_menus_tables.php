<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menús especiales de la barra (fechas patrias, shows, ferias): productos, precios del día
 * y cantidades previstas para planificar el stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('exclusive')->default(false); // true: sólo se vende lo del menú
            $table->text('notes')->nullable();
            $table->foreignId('agenda_notice_id')->nullable()->constrained('agenda_notices')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['restaurant_id', 'starts_on', 'ends_on']);
        });

        Schema::create('special_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('special_menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2)->nullable(); // null: precio de carta
            $table->unsignedInteger('planned_quantity')->nullable();
            $table->timestamps();
            $table->unique(['special_menu_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_menu_items');
        Schema::dropIfExists('special_menus');
    }
};

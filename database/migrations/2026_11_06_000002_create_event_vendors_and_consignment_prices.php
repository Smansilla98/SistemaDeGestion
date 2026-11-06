<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - Consignación con dos precios: lo que el emprendedor cobra por unidad y el precio al que vende el espacio.
 * - Puestos de un evento: emprendedores que venden lo suyo (comida, parrilla…) y le dejan un porcentaje
 *   al espacio, sin que el espacio cargue stock. El evento dice además qué vende la casa (bebidas, comida).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_products', function (Blueprint $table) {
            $table->unsignedBigInteger('entrepreneur_price_cents')->nullable()->after('price_cents');
        });
        Schema::table('consignment_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_price_cents')->nullable()->after('unit_price_cents');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->json('house_sells')->nullable()->after('status');
        });
        Schema::create('event_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entrepreneur_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('category', 20)->default('COMIDA');
            $table->decimal('percent', 5, 2)->default(0);
            $table->unsignedBigInteger('sales_cents')->nullable();
            $table->unsignedBigInteger('collected_cents')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['restaurant_id', 'entrepreneur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_vendors');
        Schema::table('events', fn (Blueprint $t) => $t->dropColumn('house_sells'));
        Schema::table('consignment_items', fn (Blueprint $t) => $t->dropColumn('sale_price_cents'));
        Schema::table('market_products', fn (Blueprint $t) => $t->dropColumn('entrepreneur_price_cents'));
    }
};

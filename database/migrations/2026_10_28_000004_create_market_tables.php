<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Emprendedores, mercado territorial y nodo agroecológico. Separado del stock del menú
     * (products/stocks): acá el producto pertenece a un emprendedor, el stock físico se mueve
     * sólo por movimientos auditados y el dinero adeudado vive en consignaciones/liquidaciones.
     */
    public function up(): void
    {
        Schema::create('entrepreneurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_name');
            $table->text('description')->nullable();
            $table->string('payment_alias', 60)->nullable();
            $table->json('brand')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['restaurant_id', 'is_active']);
        });

        Schema::create('entrepreneur_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['restaurant_id', 'name']);
        });

        Schema::create('entrepreneur_group_member', function (Blueprint $table) {
            $table->foreignId('entrepreneur_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entrepreneur_id')->constrained()->cascadeOnDelete();
            $table->primary(['entrepreneur_group_id', 'entrepreneur_id']);
        });

        Schema::create('market_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('entrepreneur_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('category', 60)->nullable();
            $table->string('name');
            $table->string('unit', 20)->default('unidad');
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->decimal('stock_quantity', 12, 3)->default(0);
            $table->decimal('min_stock', 12, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['restaurant_id', 'entrepreneur_id']);
        });

        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('entrepreneur_id')->constrained()->restrictOnDelete();
            $table->date('received_on');
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->string('status', 15)->default('ABIERTA');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['restaurant_id', 'status']);
        });

        Schema::create('consignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_product_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('unit_price_cents');
            $table->decimal('qty_delivered', 12, 3);
            $table->decimal('qty_sold', 12, 3)->default(0);
            $table->decimal('qty_returned', 12, 3)->default(0);
            $table->unsignedBigInteger('sold_cents')->default(0);
            $table->timestamps();
        });

        Schema::create('consignment_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('consignment_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('gross_cents');
            $table->unsignedBigInteger('commission_cents');
            $table->unsignedBigInteger('paid_cents');
            $table->date('paid_on');
            $table->string('method', 30)->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name');
            $table->dateTime('opens_at');
            $table->dateTime('closes_at');
            $table->date('delivery_on');
            $table->string('status', 15)->default('ABIERTO');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['restaurant_id', 'status']);
        });

        Schema::create('cycle_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->string('status', 15)->default('PENDIENTE');
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->unsignedBigInteger('paid_cents')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['order_cycle_id', 'contact_id']);
        });

        Schema::create('cycle_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('line_cents');
            $table->timestamps();
        });

        Schema::create('market_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('market_product_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('quantity', 12, 3); // con signo: + entra, − sale
            $table->decimal('balance_after', 12, 3);
            $table->foreignId('consignment_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cycle_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['market_product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['market_stock_movements', 'cycle_order_items', 'cycle_orders', 'order_cycles', 'consignment_settlements',
            'consignment_items', 'consignments', 'market_products', 'entrepreneur_group_member', 'entrepreneur_groups', 'entrepreneurs'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

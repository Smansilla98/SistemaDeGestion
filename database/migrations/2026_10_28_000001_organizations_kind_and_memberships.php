<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Organizaciones: cada restaurante (tenant) declara qué tipo de organización es, y una cuenta
     * puede participar en otras organizaciones con un rol propio en cada una (membresías).
     * No se toca users.role: el rol de la membresía se aplica sólo mientras se opera esa organización.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('restaurants', 'kind')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->string('kind', 30)->default('RESTAURANTE')->after('name');
            });
        }

        Schema::create('organization_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('role', 30);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'restaurant_id']);
            $table->index(['restaurant_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_memberships');

        if (Schema::hasColumn('restaurants', 'kind')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropColumn('kind');
            });
        }
    }
};

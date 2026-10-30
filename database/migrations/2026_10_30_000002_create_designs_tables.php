<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editor gráfico (OpenDesign): diseños por organización con sus páginas y el kit de marca compartido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('format', 40)->default('custom');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('preview_path')->nullable();
            $table->timestamps();
            $table->index(['restaurant_id', 'user_id']);
        });

        Schema::create('design_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->longText('canvas_json');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('design_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 10); // KIT: kit de marca compartido · UPLOAD: subida personal
            $table->string('title', 120);
            $table->string('path');
            $table->string('mime', 60);
            $table->unsignedBigInteger('bytes');
            $table->timestamps();
            $table->index(['restaurant_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_assets');
        Schema::dropIfExists('design_pages');
        Schema::dropIfExists('designs');
    }
};

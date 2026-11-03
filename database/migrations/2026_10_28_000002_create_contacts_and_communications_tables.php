<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contactos (personas de cada organización, con cuenta opcional), etiquetas, grupos,
     * comunicaciones de difusión, mensajes entre usuarios y la infraestructura común de avisos:
     * preferencias por categoría y registro idempotente de envíos.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20)->default('PUBLICO');
            $table->string('name');
            $table->string('alias')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('document', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('accepts_marketing')->default(false);
            $table->timestamp('marketing_consent_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['restaurant_id', 'kind']);
            $table->index(['restaurant_id', 'name']);
            $table->unique(['restaurant_id', 'user_id']);
        });

        Schema::create('contact_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name', 60);
            $table->timestamps();
            $table->unique(['restaurant_id', 'name']);
        });

        Schema::create('contact_tag', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['contact_id', 'contact_tag_id']);
        });

        Schema::create('contact_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['restaurant_id', 'name']);
        });

        Schema::create('contact_group_member', function (Blueprint $table) {
            $table->foreignId('contact_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->primary(['contact_group_id', 'contact_id']);
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('link')->nullable();
            $table->json('audience')->nullable();
            $table->boolean('send_email')->default(false);
            $table->string('status', 15)->default('BORRADOR');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamps();
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('channels', 60)->default('');
            $table->timestamps();
            $table->unique(['campaign_id', 'contact_id']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30);
            $table->boolean('in_app')->default(true);
            $table->boolean('email')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'category']);
        });

        Schema::create('notification_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key', 191);
            $table->timestamp('created_at')->nullable();
            $table->unique(['user_id', 'key']);
        });

        Schema::create('user_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['recipient_id', 'read_at']);
            $table->index(['sender_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['user_messages', 'notification_dispatches', 'notification_preferences', 'campaign_recipients',
            'campaigns', 'contact_group_member', 'contact_groups', 'contact_tag', 'contact_tags', 'contacts'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

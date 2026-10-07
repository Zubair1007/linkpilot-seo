<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('target_domain');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->index();
            $table->boolean('is_verified')->default(false);
            $table->string('verification_method')->nullable(); // 'dns', 'file', 'meta', 'oauth'
            $table->string('verification_token')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('search_engine_auth_type')->nullable(); // 'bing_webmaster', 'google_sc', 'indexnow'
            $table->timestamps();
        });

        Schema::create('search_engine_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider'); // 'bing_webmaster', 'google_search_console', 'indexnow'
            $table->string('property_url');
            $table->text('encrypted_credentials')->nullable(); // Encrypted credentials/tokens
            $table->boolean('is_authorized')->default(false);
            $table->string('authorization_status')->default('pending'); // 'authorized', 'expired', 'revoked', 'pending'
            $table->timestamp('authorized_at')->nullable();
            $table->integer('quota_daily')->default(10000);
            $table->integer('quota_used_today')->default(0);
            $table->timestamp('last_quota_reset_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_engine_properties');
        Schema::dropIfExists('domains');
        Schema::dropIfExists('projects');
    }
};

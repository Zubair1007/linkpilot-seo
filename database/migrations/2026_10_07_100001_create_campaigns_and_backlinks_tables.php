<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('check_frequency')->default('24h'); // '24h', '72h', '7d', '14d', '30d'
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('backlinks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->text('source_url');
            $table->text('target_url');
            $table->string('anchor_text')->nullable();
            $table->string('link_type')->default('dofollow'); // 'dofollow', 'nofollow', 'ugc', 'sponsored', 'unknown'
            $table->json('rel_attributes')->nullable();
            $table->integer('http_status')->nullable();
            $table->text('final_url')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('robots_status')->nullable(); // 'index, follow', 'noindex', etc.
            $table->string('indexability')->default('unknown'); // 'indexable', 'not_indexable', 'redirect', 'broken', 'unknown'
            
            // Health & Live state
            $table->boolean('is_live')->default(true);
            $table->boolean('is_indexed')->default(false);
            
            // Explicit separate statuses
            $table->string('crawl_status')->default('PENDING'); // 'PENDING', 'DISCOVERED', 'CRAWLED', 'ERROR'
            $table->string('index_status')->default('UNKNOWN'); // 'UNKNOWN', 'PENDING', 'DISCOVERED', 'CRAWLED', 'INDEXED', 'NOT_INDEXED', 'ERROR', 'LOST'
            $table->string('discovery_status')->default('PENDING'); // 'UNKNOWN', 'PENDING', 'SUBMITTED', 'DISCOVERED'
            
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamp('last_indexed_check_at')->nullable();
            
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(5);
            $table->timestamp('next_retry_at')->nullable();
            $table->text('last_error')->nullable();
            
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'is_live']);
            $table->index(['campaign_id', 'index_status']);
            $table->index(['crawl_status']);
        });

        Schema::create('backlink_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backlink_id')->constrained()->cascadeOnDelete();
            $table->string('event_type'); // 'added', 'verified', 'changed', 'lost', 'restored', 'index_status_changed'
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backlink_events');
        Schema::dropIfExists('backlinks');
        Schema::dropIfExists('campaigns');
    }
};

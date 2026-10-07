<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backlink_id')->constrained()->cascadeOnDelete();
            $table->text('source_url');
            $table->integer('http_status')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->integer('redirect_count')->default(0);
            $table->text('final_url')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('meta_robots')->nullable();
            $table->string('x_robots_tag')->nullable();
            $table->string('robots_txt_status')->nullable(); // 'allowed', 'disallowed', 'fetch_failed'
            $table->string('content_type')->nullable();
            $table->boolean('is_https')->default(false);
            $table->string('page_title')->nullable();
            $table->boolean('backlink_found')->default(false);
            $table->text('target_url_found')->nullable();
            $table->string('anchor_text_found')->nullable();
            $table->json('rel_attributes_found')->nullable();
            $table->boolean('passed')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('discovery_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backlink_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // 'bing', 'google_sc', 'indexnow', 'native_crawler'
            $table->string('method'); // 'url_inspection', 'batch_submission', 'indexnow_key', 'live_fetch'
            $table->string('status')->default('pending'); // 'pending', 'running', 'completed', 'failed', 'retrying'
            $table->integer('attempt')->default(1);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('response_code')->nullable();
            $table->text('response_message')->nullable();
            $table->json('payload_metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('external_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider'); // 'google_search_console', 'bing_webmaster', 'indexnow', 'crawler'
            $table->string('endpoint_category'); // 'inspection', 'submission', 'auth', 'quota'
            $table->text('url');
            $table->string('http_method', 10)->default('GET');
            $table->integer('response_code')->nullable();
            $table->integer('latency_ms')->default(0);
            $table->json('sanitized_request')->nullable();
            $table->json('sanitized_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // 'created_project', 'submitted_urls', 'verified_domain', etc.
            $table->string('resource_type')->nullable();
            $table->string('resource_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('type'); // 'campaign_summary', 'backlink_audit', 'lost_backlinks', 'index_status', 'api_activity'
            $table->string('format')->default('json'); // 'json', 'csv', 'xlsx', 'pdf'
            $table->json('summary_metrics')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('external_api_logs');
        Schema::dropIfExists('discovery_jobs');
        Schema::dropIfExists('health_checks');
    }
};

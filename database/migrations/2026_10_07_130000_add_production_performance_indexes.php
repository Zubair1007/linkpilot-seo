<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backlinks', function (Blueprint $table) {
            $table->index('next_retry_at', 'idx_backlinks_next_retry_at');
            $table->index(['project_id', 'index_status'], 'idx_backlinks_proj_idx_status');
            $table->index('domain_rating', 'idx_backlinks_domain_rating');
            $table->index('is_indexed', 'idx_backlinks_is_indexed');
        });

        Schema::table('discovery_jobs', function (Blueprint $table) {
            $table->index(['provider', 'status'], 'idx_discovery_provider_status');
            $table->index('scheduled_at', 'idx_discovery_scheduled_at');
        });

        Schema::table('external_api_logs', function (Blueprint $table) {
            $table->index(['provider', 'created_at'], 'idx_api_logs_provider_created');
            $table->index('created_at', 'idx_api_logs_created_at');
        });

        Schema::table('health_checks', function (Blueprint $table) {
            $table->index(['backlink_id', 'created_at'], 'idx_health_backlink_created');
        });
    }

    public function down(): void
    {
        Schema::table('health_checks', function (Blueprint $table) {
            $table->dropIndex('idx_health_backlink_created');
        });

        Schema::table('external_api_logs', function (Blueprint $table) {
            $table->dropIndex('idx_api_logs_provider_created');
            $table->dropIndex('idx_api_logs_created_at');
        });

        Schema::table('discovery_jobs', function (Blueprint $table) {
            $table->dropIndex('idx_discovery_provider_status');
            $table->dropIndex('idx_discovery_scheduled_at');
        });

        Schema::table('backlinks', function (Blueprint $table) {
            $table->dropIndex('idx_backlinks_next_retry_at');
            $table->dropIndex('idx_backlinks_proj_idx_status');
            $table->dropIndex('idx_backlinks_domain_rating');
            $table->dropIndex('idx_backlinks_is_indexed');
        });
    }
};

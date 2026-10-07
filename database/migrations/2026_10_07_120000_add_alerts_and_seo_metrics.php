<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('slack_webhook_url')->nullable()->after('settings');
            $table->string('alert_email')->nullable()->after('slack_webhook_url');
            $table->boolean('alerts_enabled')->default(true)->after('alert_email');
        });

        Schema::table('backlinks', function (Blueprint $table) {
            $table->integer('domain_rating')->nullable()->after('robots_status'); // Ahrefs DR (0-100)
            $table->integer('domain_authority')->nullable()->after('domain_rating'); // Moz DA (0-100)
            $table->integer('page_authority')->nullable()->after('domain_authority'); // Moz PA (0-100)
            $table->integer('authority_score')->nullable()->after('page_authority'); // SEMrush AS (0-100)
            $table->timestamp('metrics_updated_at')->nullable()->after('authority_score');
        });
    }

    public function down(): void
    {
        Schema::table('backlinks', function (Blueprint $table) {
            $table->dropColumn([
                'domain_rating',
                'domain_authority',
                'page_authority',
                'authority_score',
                'metrics_updated_at',
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'slack_webhook_url',
                'alert_email',
                'alerts_enabled',
            ]);
        });
    }
};

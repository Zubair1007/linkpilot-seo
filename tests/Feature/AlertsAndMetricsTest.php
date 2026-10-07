<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\User;
use App\Services\Alerts\AlertNotificationService;
use App\Services\Metrics\SeoMetricsProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertsAndMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetches_and_persists_seo_authority_metrics(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'SEO Metrics Project',
            'slug' => 'seo-metrics-project',
            'target_domain' => 'acme.io',
        ]);
        $campaign = Campaign::create([
            'project_id' => $project->id,
            'name' => 'Metrics Campaign',
        ]);
        $backlink = Backlink::create([
            'project_id' => $project->id,
            'campaign_id' => $campaign->id,
            'source_url' => 'https://searchengineland.com/seo-guide',
            'target_url' => 'https://acme.io/features',
        ]);

        $service = app(SeoMetricsProviderService::class);
        $result = $service->fetchMetricsForBacklink($backlink);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('moz_da', $result['metrics']);
        $this->assertArrayHasKey('ahrefs_dr', $result['metrics']);
        $this->assertArrayHasKey('semrush_as', $result['metrics']);

        $fresh = $backlink->fresh();
        $this->assertNotNull($fresh->domain_authority);
        $this->assertNotNull($fresh->domain_rating);
        $this->assertNotNull($fresh->authority_score);
    }

    public function test_dispatches_lost_backlink_alert(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Alerts Project',
            'slug' => 'alerts-project',
            'target_domain' => 'acme.io',
            'slack_webhook_url' => 'https://hooks.slack.com/services/mock/test/webhook',
            'alerts_enabled' => true,
        ]);
        $campaign = Campaign::create([
            'project_id' => $project->id,
            'name' => 'Alerts Campaign',
        ]);
        $backlink = Backlink::create([
            'project_id' => $project->id,
            'campaign_id' => $campaign->id,
            'source_url' => 'https://blog.partner.com/lost-link',
            'target_url' => 'https://acme.io/',
            'is_live' => false,
        ]);

        $alertService = app(AlertNotificationService::class);
        $dispatchResult = $alertService->sendLostBacklinkAlert($backlink, 'HTTP 404 Not Found');

        $this->assertEquals('dispatched', $dispatchResult['status']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'lost_backlink_alert_dispatched',
            'resource_id' => (string) $backlink->id,
        ]);
    }

    public function test_pdf_report_export_returns_pdf_stream(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Report Project',
            'slug' => 'report-project',
            'target_domain' => 'acme.io',
        ]);
        $campaign = Campaign::create([
            'project_id' => $project->id,
            'name' => 'Report Campaign',
        ]);
        Backlink::create([
            'project_id' => $project->id,
            'campaign_id' => $campaign->id,
            'source_url' => 'https://highauth.com/post',
            'target_url' => 'https://acme.io/',
            'is_live' => true,
            'http_status' => 200,
            'crawl_status' => 'CRAWLED',
            'index_status' => 'INDEXED',
        ]);

        $response = $this->actingAs($user, 'sanctum')->get('/api/v1/reports/export-pdf?project_id=' . $project->id);

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }
}

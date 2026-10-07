<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Project;
use App\Models\User;
use App\Services\Import\BacklinkImportService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthAndFreeLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok_and_checks(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'app_version',
                'environment',
                'checks' => [
                    'database' => ['status', 'driver', 'latency_ms'],
                    'queue' => ['status', 'connection'],
                    'limits' => ['max_urls_per_import', 'max_url_checks_per_run', 'max_retries', 'request_timeout_sec'],
                ],
            ])
            ->assertJson([
                'status' => 'ok',
            ]);
    }

    public function test_api_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
            ]);
    }

    public function test_import_enforces_max_urls_per_import_limit(): void
    {
        config(['linkpilot.limits.max_urls_per_import' => 3]);

        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'slug' => 'test-project',
            'target_domain' => 'test.org',
        ]);
        $campaign = Campaign::create([
            'project_id' => $project->id,
            'name' => 'Test Campaign',
        ]);

        $importer = app(BacklinkImportService::class);
        $rawText = "https://a.com https://test.org AnchorA\n" .
                   "https://b.com https://test.org AnchorB\n" .
                   "https://c.com https://test.org AnchorC\n" .
                   "https://d.com https://test.org AnchorD\n";

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Import batch exceeds configured limit of 3 URLs');

        $importer->importFromText($rawText, $campaign);
    }
}

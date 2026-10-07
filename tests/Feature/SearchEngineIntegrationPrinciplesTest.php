<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\SearchEngineProperty;
use App\Models\User;
use App\Services\SearchEngines\BingWebmasterProvider;
use App\Services\SearchEngines\GoogleSearchConsoleProvider;
use App\Services\SearchEngines\IndexNowProvider;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchEngineIntegrationPrinciplesTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_are_encrypted_in_database_principle_10(): void
    {
        $user = User::factory()->create();
        $property = SearchEngineProperty::create([
            'user_id' => $user->id,
            'provider' => 'bing_webmaster',
            'property_url' => 'https://verified-site.com',
            'encrypted_credentials' => [
                'api_key' => 'super_secret_bing_api_key_12345',
            ],
            'is_authorized' => true,
        ]);

        // Direct DB inspection must not contain plaintext secret
        $raw = DB::table('search_engine_properties')->where('id', $property->id)->first();
        $this->assertStringNotContainsString('super_secret_bing_api_key_12345', $raw->encrypted_credentials);

        // Eloquent model decrypts transparently
        $fresh = SearchEngineProperty::find($property->id);
        $this->assertEquals('super_secret_bing_api_key_12345', $fresh->encrypted_credentials['api_key']);
    }

    public function test_bing_submission_rejected_without_authorized_property_principle_3_and_4(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Demo Project',
            'slug' => 'demo-project',
            'target_domain' => 'unauthorized-domain.com',
        ]);
        $campaign = Campaign::create([
            'project_id' => $project->id,
            'name' => 'Test Campaign',
        ]);
        $backlink = Backlink::create([
            'project_id' => $project->id,
            'campaign_id' => $campaign->id,
            'source_url' => 'https://thirdparty.com/article',
            'target_url' => 'https://unauthorized-domain.com/landing',
        ]);

        $bingProvider = app(BingWebmasterProvider::class);
        $result = $bingProvider->submitUrl($backlink);

        $this->assertFalse($result['success']);
        $this->assertEquals(403, $result['response_code']);
        $this->assertStringContainsString('Principle #3 enforced', $result['message']);
    }

    public function test_google_inspection_rejected_without_authorized_property_principle_5(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Demo Project',
            'slug' => 'demo-project-gsc',
            'target_domain' => 'unknown-property.com',
        ]);
        $campaign = Campaign::create([
            'project_id' => $project->id,
            'name' => 'Test Campaign GSC',
        ]);
        $backlink = Backlink::create([
            'project_id' => $project->id,
            'campaign_id' => $campaign->id,
            'source_url' => 'https://blog.com/post',
            'target_url' => 'https://unknown-property.com/service',
        ]);

        $gscProvider = app(GoogleSearchConsoleProvider::class);
        $result = $gscProvider->inspectIndexStatus($backlink);

        $this->assertFalse($result['success']);
        $this->assertEquals('UNKNOWN', $result['index_status']);
        $this->assertStringContainsString('Principle #5 enforced', $result['message']);
    }

    public function test_google_indexing_api_rejected_for_non_job_posting_or_broadcast_principle_6(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Principle #6 Enforced');

        $user = User::factory()->create();
        $property = SearchEngineProperty::create([
            'user_id' => $user->id,
            'provider' => 'google_search_console',
            'property_url' => 'https://acme.io',
        ]);

        $gscProvider = app(GoogleSearchConsoleProvider::class);
        $gscProvider->submitIndexingApi('https://acme.io/blog-post', 'Article', $property);
    }
}

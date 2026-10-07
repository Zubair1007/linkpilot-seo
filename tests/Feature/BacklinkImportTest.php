<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Project;
use App\Models\User;
use App\Services\Import\BacklinkImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklinkImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_backlinks_with_validation_and_deduplication(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Import Project',
            'slug' => 'import-proj',
            'target_domain' => 'acme.io',
        ]);
        $campaign = Campaign::create([
            'project_id' => $project->id,
            'name' => 'Bulk Import Campaign',
            'check_frequency' => '24h',
        ]);

        $csv = <<<CSV
source_url,target_url,anchor_text
https://partner1.com/page1,https://acme.io/features,Acme Features
https://partner2.com/page2,https://acme.io/pricing,Acme Pricing
https://partner1.com/page1,https://acme.io/features,Acme Features
invalid-url-here,https://acme.io/test,Invalid
https://partner3.com/page3,,Missing Target
CSV;

        $importer = app(BacklinkImportService::class);
        $result = $importer->importFromCsv($csv, $campaign);

        $this->assertEquals(2, $result['total_imported']);
        $this->assertEquals(1, $result['total_duplicates']);
        $this->assertEquals(2, $result['total_errors']);

        $this->assertDatabaseHas('backlinks', [
            'campaign_id' => $campaign->id,
            'source_url' => 'https://partner1.com/page1',
        ]);
        $this->assertDatabaseHas('backlink_events', [
            'event_type' => 'added',
        ]);
    }
}

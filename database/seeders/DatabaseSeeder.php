<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Backlink;
use App\Models\BacklinkEvent;
use App\Models\Campaign;
use App\Models\DiscoveryJob;
use App\Models\Domain;
use App\Models\ExternalApiLog;
use App\Models\HealthCheck;
use App\Models\Project;
use App\Models\Report;
use App\Models\SearchEngineProperty;
use App\Models\User;
use App\Services\Metrics\SeoMetricsProviderService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        $admin = User::firstOrCreate([
            'email' => 'admin@linkpilot.io',
        ], [
            'name' => 'Alex Morgan (Admin)',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'api_rate_limit' => 120,
        ]);

        $specialist = User::firstOrCreate([
            'email' => 'specialist@linkpilot.io',
        ], [
            'name' => 'Sarah Connor (SEO Lead)',
            'password' => Hash::make('password'),
            'role' => 'seo_specialist',
            'status' => 'active',
            'api_rate_limit' => 60,
        ]);

        // Generate Sanctum tokens for API testing
        $adminToken = $admin->createToken('admin-api-key', ['read', 'write', 'admin'])->plainTextToken;
        $specialistToken = $specialist->createToken('specialist-api-key', ['read', 'write'])->plainTextToken;

        // 2. Projects
        $project1 = Project::firstOrCreate([
            'slug' => 'acme-saas-growth',
        ], [
            'user_id' => $admin->id,
            'name' => 'Acme Cloud SaaS Platform',
            'target_domain' => 'acme.io',
            'description' => 'Global backlink discovery, crawler indexing monitor, and organic search validation.',
            'alert_email' => 'admin@linkpilot.io',
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/mock_sample_webhook_key',
            'alerts_enabled' => true,
            'is_active' => true,
        ]);

        $project2 = Project::firstOrCreate([
            'slug' => 'techradar-outreach',
        ], [
            'user_id' => $specialist->id,
            'name' => 'TechRadar Media Network',
            'target_domain' => 'techradar.internal-demo.com',
            'description' => 'Tier 1 editorial publications and authority link audit campaigns.',
            'is_active' => true,
        ]);

        // 3. Domains
        $domain1 = Domain::firstOrCreate([
            'project_id' => $project1->id,
            'domain' => 'acme.io',
        ], [
            'is_verified' => true,
            'verification_method' => 'dns',
            'verification_token' => 'lp-dns-verify-acme77821',
            'verified_at' => Carbon::now()->subDays(30),
        ]);

        $domain2 = Domain::firstOrCreate([
            'project_id' => $project1->id,
            'domain' => 'blog.acme.io',
        ], [
            'is_verified' => true,
            'verification_method' => 'file',
            'verification_token' => 'lp-file-verify-blogacme',
            'verified_at' => Carbon::now()->subDays(15),
        ]);

        // 4. Authorized Search Engine Properties (Encrypted storage)
        $gscProperty = SearchEngineProperty::firstOrCreate([
            'property_url' => 'https://acme.io/',
            'provider' => 'google_search_console',
        ], [
            'user_id' => $admin->id,
            'project_id' => $project1->id,
            'domain_id' => $domain1->id,
            'encrypted_credentials' => [
                'access_token' => 'mock_gsc_oauth_token_' . Str::random(20),
                'client_id' => '1029384756.apps.googleusercontent.com',
            ],
            'is_authorized' => true,
            'authorization_status' => 'authorized',
            'authorized_at' => Carbon::now()->subDays(20),
            'quota_daily' => 2000,
            'quota_used_today' => 42,
        ]);

        $bingProperty = SearchEngineProperty::firstOrCreate([
            'property_url' => 'https://acme.io/',
            'provider' => 'bing_webmaster',
        ], [
            'user_id' => $admin->id,
            'project_id' => $project1->id,
            'domain_id' => $domain1->id,
            'encrypted_credentials' => [
                'api_key' => 'bing_wm_sec_' . Str::random(24),
            ],
            'is_authorized' => true,
            'authorization_status' => 'authorized',
            'authorized_at' => Carbon::now()->subDays(20),
            'quota_daily' => 10000,
            'quota_used_today' => 128,
        ]);

        $indexNowProperty = SearchEngineProperty::firstOrCreate([
            'property_url' => 'https://acme.io/',
            'provider' => 'indexnow',
        ], [
            'user_id' => $admin->id,
            'project_id' => $project1->id,
            'domain_id' => $domain1->id,
            'encrypted_credentials' => [
                'key' => 'e98f02938ab4c9103e',
                'key_location' => 'https://acme.io/e98f02938ab4c9103e.txt',
            ],
            'is_authorized' => true,
            'authorization_status' => 'authorized',
            'authorized_at' => Carbon::now()->subDays(20),
            'quota_daily' => 10000,
            'quota_used_today' => 15,
        ]);

        // 5. Campaigns
        $campaign1 = Campaign::firstOrCreate([
            'name' => 'Q1 High Authority Editorial Backlinks',
            'project_id' => $project1->id,
        ], [
            'description' => 'Monitoring top-tier tech publications, guest columns, and industry partner citations.',
            'check_frequency' => '24h',
            'is_active' => true,
            'next_run_at' => Carbon::now()->addHours(6),
            'last_run_at' => Carbon::now()->subHours(18),
        ]);

        $campaign2 = Campaign::firstOrCreate([
            'name' => 'SaaS Directories & Resource Lists',
            'project_id' => $project1->id,
        ], [
            'description' => 'Product directories, curated startup lists, and API aggregator listings.',
            'check_frequency' => '72h',
            'is_active' => true,
            'next_run_at' => Carbon::now()->addHours(24),
            'last_run_at' => Carbon::now()->subHours(48),
        ]);

        // 6. Realistic Backlink Dataset covering all test scenarios
        $linksData = [
            // Scenario 1: Healthy, Live, Verified, Indexed Backlink (dofollow)
            [
                'source_url' => 'https://technews-weekly.com/best-cloud-saas-2026',
                'target_url' => 'https://acme.io/features',
                'anchor_text' => 'Acme Cloud platform',
                'link_type' => 'dofollow',
                'rel_attributes' => [],
                'http_status' => 200,
                'final_url' => 'https://technews-weekly.com/best-cloud-saas-2026',
                'canonical_url' => 'https://technews-weekly.com/best-cloud-saas-2026',
                'robots_status' => 'index, follow',
                'indexability' => 'indexable',
                'is_live' => true,
                'is_indexed' => true,
                'crawl_status' => 'CRAWLED',
                'index_status' => 'INDEXED',
                'discovery_status' => 'SUBMITTED',
                'first_seen_at' => Carbon::now()->subDays(20),
                'last_seen_at' => Carbon::now()->subHours(2),
                'last_verified_at' => Carbon::now()->subHours(2),
                'last_crawled_at' => Carbon::now()->subHours(2),
                'last_indexed_check_at' => Carbon::now()->subHours(2),
            ],
            // Scenario 2: Live, Crawled, but NOT_INDEXED (meta noindex present)
            [
                'source_url' => 'https://internal-directory.biz/staging-preview-acme',
                'target_url' => 'https://acme.io/pricing',
                'anchor_text' => 'transparent SaaS pricing',
                'link_type' => 'dofollow',
                'rel_attributes' => [],
                'http_status' => 200,
                'final_url' => 'https://internal-directory.biz/staging-preview-acme',
                'canonical_url' => 'https://internal-directory.biz/staging-preview-acme',
                'robots_status' => 'noindex, follow',
                'indexability' => 'not_indexable',
                'is_live' => true,
                'is_indexed' => false,
                'crawl_status' => 'CRAWLED',
                'index_status' => 'NOT_INDEXED',
                'discovery_status' => 'DISCOVERED',
                'first_seen_at' => Carbon::now()->subDays(15),
                'last_seen_at' => Carbon::now()->subHours(5),
                'last_verified_at' => Carbon::now()->subHours(5),
                'last_crawled_at' => Carbon::now()->subHours(5),
                'last_indexed_check_at' => Carbon::now()->subHours(5),
            ],
            // Scenario 3: Lost / Removed Backlink (Source page 200 OK, but anchor tag deleted)
            [
                'source_url' => 'https://devdigest.net/top-developer-tools',
                'target_url' => 'https://acme.io/docs',
                'anchor_text' => 'Acme API Documentation',
                'link_type' => 'dofollow',
                'rel_attributes' => [],
                'http_status' => 200,
                'final_url' => 'https://devdigest.net/top-developer-tools',
                'canonical_url' => 'https://devdigest.net/top-developer-tools',
                'robots_status' => 'index, follow',
                'indexability' => 'indexable',
                'is_live' => false, // LOST!
                'is_indexed' => false,
                'crawl_status' => 'CRAWLED',
                'index_status' => 'LOST',
                'discovery_status' => 'DISCOVERED',
                'first_seen_at' => Carbon::now()->subDays(25),
                'last_seen_at' => Carbon::now()->subDays(4),
                'last_verified_at' => Carbon::now()->subHours(1),
                'last_crawled_at' => Carbon::now()->subHours(1),
                'last_error' => 'Target URL was not found in source HTML during automated crawler verification.',
            ],
            // Scenario 4: Broken Source Page (HTTP 404 Not Found)
            [
                'source_url' => 'https://oldstartuparchive.org/post-404-archive',
                'target_url' => 'https://acme.io/',
                'anchor_text' => 'Acme Official Portal',
                'link_type' => 'unknown',
                'rel_attributes' => [],
                'http_status' => 404,
                'final_url' => 'https://oldstartuparchive.org/post-404-archive',
                'canonical_url' => null,
                'robots_status' => null,
                'indexability' => 'broken',
                'is_live' => false,
                'is_indexed' => false,
                'crawl_status' => 'ERROR',
                'index_status' => 'ERROR',
                'discovery_status' => 'PENDING',
                'first_seen_at' => Carbon::now()->subDays(10),
                'last_seen_at' => Carbon::now()->subDays(6),
                'last_verified_at' => Carbon::now()->subHours(3),
                'last_crawled_at' => Carbon::now()->subHours(3),
                'last_error' => 'Remote web server returned HTTP 404 Not Found',
            ],
            // Scenario 5: Redirected Backlink (301 Permanent Redirect)
            [
                'source_url' => 'https://techblog.co/old-cloud-review',
                'target_url' => 'https://acme.io/enterprise',
                'anchor_text' => 'Acme Enterprise Tier',
                'link_type' => 'dofollow',
                'rel_attributes' => [],
                'http_status' => 301,
                'final_url' => 'https://techblog.co/insights/2026-cloud-review',
                'canonical_url' => 'https://techblog.co/insights/2026-cloud-review',
                'robots_status' => 'index, follow',
                'indexability' => 'redirect',
                'is_live' => true,
                'is_indexed' => true,
                'crawl_status' => 'CRAWLED',
                'index_status' => 'INDEXED',
                'discovery_status' => 'SUBMITTED',
                'first_seen_at' => Carbon::now()->subDays(18),
                'last_seen_at' => Carbon::now()->subHours(4),
                'last_verified_at' => Carbon::now()->subHours(4),
                'last_crawled_at' => Carbon::now()->subHours(4),
            ],
            // Scenario 6: Canonicalized URL (Canonical pointing to different article)
            [
                'source_url' => 'https://syndicated-news.io/repost/acme-interview',
                'target_url' => 'https://acme.io/about',
                'anchor_text' => 'Founders interview at Acme',
                'link_type' => 'dofollow',
                'rel_attributes' => [],
                'http_status' => 200,
                'final_url' => 'https://syndicated-news.io/repost/acme-interview',
                'canonical_url' => 'https://original-publisher.com/story/acme-interview',
                'robots_status' => 'index, follow',
                'indexability' => 'indexable',
                'is_live' => true,
                'is_indexed' => true,
                'crawl_status' => 'CRAWLED',
                'index_status' => 'INDEXED',
                'discovery_status' => 'SUBMITTED',
                'first_seen_at' => Carbon::now()->subDays(12),
                'last_seen_at' => Carbon::now()->subHours(8),
                'last_verified_at' => Carbon::now()->subHours(8),
                'last_crawled_at' => Carbon::now()->subHours(8),
            ],
            // Scenario 7: Nofollow / Sponsored Attribute
            [
                'source_url' => 'https://influencer-marketing-hub.com/sponsored-roundup',
                'target_url' => 'https://acme.io/solutions',
                'anchor_text' => 'sponsored link insertion',
                'link_type' => 'sponsored',
                'rel_attributes' => ['nofollow', 'sponsored'],
                'http_status' => 200,
                'final_url' => 'https://influencer-marketing-hub.com/sponsored-roundup',
                'canonical_url' => 'https://influencer-marketing-hub.com/sponsored-roundup',
                'robots_status' => 'index, follow',
                'indexability' => 'indexable',
                'is_live' => true,
                'is_indexed' => true,
                'crawl_status' => 'CRAWLED',
                'index_status' => 'INDEXED',
                'discovery_status' => 'SUBMITTED',
                'first_seen_at' => Carbon::now()->subDays(8),
                'last_seen_at' => Carbon::now()->subHours(6),
                'last_verified_at' => Carbon::now()->subHours(6),
                'last_crawled_at' => Carbon::now()->subHours(6),
            ],
            // Scenario 8: New Pending Discovery Backlink
            [
                'source_url' => 'https://fintech-insider.org/emerging-solutions',
                'target_url' => 'https://acme.io/security',
                'anchor_text' => 'SOC2 compliant SaaS cloud',
                'link_type' => 'dofollow',
                'rel_attributes' => [],
                'http_status' => 200,
                'final_url' => 'https://fintech-insider.org/emerging-solutions',
                'canonical_url' => 'https://fintech-insider.org/emerging-solutions',
                'robots_status' => 'index, follow',
                'indexability' => 'indexable',
                'is_live' => true,
                'is_indexed' => false,
                'crawl_status' => 'DISCOVERED',
                'index_status' => 'PENDING',
                'discovery_status' => 'PENDING',
                'first_seen_at' => Carbon::now()->subHours(2),
                'last_seen_at' => Carbon::now()->subHours(2),
                'last_verified_at' => Carbon::now()->subHours(2),
                'last_crawled_at' => Carbon::now()->subHours(2),
            ],
            // Scenario 9: Failed check with Retry Backoff Scheduled
            [
                'source_url' => 'https://flaky-partner-host.com/resources',
                'target_url' => 'https://acme.io/integrations',
                'anchor_text' => 'partner ecosystem',
                'link_type' => 'dofollow',
                'rel_attributes' => [],
                'http_status' => 503,
                'final_url' => 'https://flaky-partner-host.com/resources',
                'canonical_url' => null,
                'robots_status' => null,
                'indexability' => 'broken',
                'is_live' => false,
                'is_indexed' => false,
                'crawl_status' => 'ERROR',
                'index_status' => 'ERROR',
                'discovery_status' => 'PENDING',
                'first_seen_at' => Carbon::now()->subDays(5),
                'last_seen_at' => Carbon::now()->subDays(2),
                'last_verified_at' => Carbon::now()->subMinutes(30),
                'retry_count' => 2,
                'max_retries' => 5,
                'next_retry_at' => Carbon::now()->addMinutes(60),
                'last_error' => 'cURL error: Connection timed out after 5000 milliseconds (503 Service Unavailable)',
            ],
        ];

        $metricsService = new SeoMetricsProviderService();

        foreach ($linksData as $data) {
            $bl = Backlink::create(array_merge($data, [
                'project_id' => $project1->id,
                'campaign_id' => $campaign1->id,
                'domain_id' => $domain1->id,
            ]));

            // Populate SEO metrics (DR, DA, PA, AS)
            $metricsService->fetchMetricsForBacklink($bl);

            // Historical events
            $bl->recordEvent('added', null, [
                'source_url' => $bl->source_url,
                'target_url' => $bl->target_url,
                'anchor_text' => $bl->anchor_text,
            ], 'Backlink record created via campaign bulk import.');

            if ($bl->index_status === 'LOST') {
                $bl->recordEvent('lost', [
                    'is_live' => true,
                    'anchor_text' => $bl->anchor_text,
                ], [
                    'is_live' => false,
                    'error' => $bl->last_error,
                ], 'Backlink disappeared from remote page during scheduled health check.');
            } elseif ($bl->http_status === 301) {
                $bl->recordEvent('changed', [
                    'final_url' => $bl->source_url,
                ], [
                    'final_url' => $bl->final_url,
                ], 'HTTP 301 Redirect detected.');
            } else {
                $bl->recordEvent('verified', null, [
                    'http_status' => $bl->http_status,
                    'is_live' => $bl->is_live,
                    'index_status' => $bl->index_status,
                ], 'Routine backlink verification passed.');
            }

            // Health check record
            HealthCheck::create([
                'backlink_id' => $bl->id,
                'source_url' => $bl->source_url,
                'http_status' => $bl->http_status,
                'response_time_ms' => rand(120, 680),
                'redirect_count' => $bl->http_status === 301 ? 1 : 0,
                'final_url' => $bl->final_url,
                'canonical_url' => $bl->canonical_url,
                'meta_robots' => $bl->robots_status,
                'x_robots_tag' => null,
                'robots_txt_status' => 'allowed',
                'content_type' => 'text/html; charset=UTF-8',
                'is_https' => true,
                'page_title' => 'Industry Insights & SaaS Directory',
                'backlink_found' => $bl->is_live,
                'target_url_found' => $bl->is_live ? $bl->target_url : null,
                'anchor_text_found' => $bl->anchor_text,
                'rel_attributes_found' => $bl->rel_attributes,
                'passed' => $bl->is_live && $bl->http_status === 200,
                'error_message' => $bl->last_error,
                'created_at' => Carbon::now()->subMinutes(rand(10, 180)),
            ]);

            // Discovery job record
            DiscoveryJob::create([
                'backlink_id' => $bl->id,
                'provider' => rand(0, 1) ? 'bing' : 'indexnow',
                'method' => 'batch_submission',
                'status' => $bl->is_live ? 'completed' : 'failed',
                'attempt' => 1,
                'scheduled_at' => Carbon::now()->subHours(2),
                'started_at' => Carbon::now()->subHours(2),
                'completed_at' => Carbon::now()->subHours(2),
                'response_code' => $bl->is_live ? 200 : 400,
                'response_message' => $bl->is_live ? 'Submitted successfully to search-engine discovery protocol.' : 'Discovery submission failed.',
            ]);
        }

        // 7. External API Request Logs (Principle #9)
        ExternalApiLog::create([
            'user_id' => $admin->id,
            'provider' => 'google_search_console',
            'endpoint_category' => 'inspection',
            'url' => 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect',
            'http_method' => 'POST',
            'response_code' => 200,
            'latency_ms' => 432,
            'sanitized_request' => ['inspectionUrl' => 'https://acme.io/features', 'siteUrl' => 'https://acme.io/'],
            'sanitized_response' => [
                'coverageState' => 'Indexed, not submitted in sitemap',
                'verdict' => 'PASS',
                'robotsTxtState' => 'ALLOWED',
                'pageFetchState' => 'SUCCESSFUL',
            ],
            'created_at' => Carbon::now()->subHours(2),
        ]);

        ExternalApiLog::create([
            'user_id' => $admin->id,
            'provider' => 'bing_webmaster',
            'endpoint_category' => 'submission',
            'url' => 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrl',
            'http_method' => 'POST',
            'response_code' => 200,
            'latency_ms' => 312,
            'sanitized_request' => ['siteUrl' => 'https://acme.io', 'url' => 'https://acme.io/features'],
            'sanitized_response' => ['d' => null],
            'created_at' => Carbon::now()->subHours(4),
        ]);

        ExternalApiLog::create([
            'user_id' => $admin->id,
            'provider' => 'indexnow',
            'endpoint_category' => 'submission',
            'url' => 'https://api.indexnow.org/indexnow',
            'http_method' => 'POST',
            'response_code' => 200,
            'latency_ms' => 195,
            'sanitized_request' => ['host' => 'acme.io', 'key' => '[REDACTED]', 'urlList' => ['https://acme.io/features']],
            'sanitized_response' => ['status' => 200, 'message' => 'OK'],
            'created_at' => Carbon::now()->subHours(5),
        ]);

        // 8. Audit logs
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'project_created',
            'resource_type' => 'Project',
            'resource_id' => (string) $project1->id,
            'ip_address' => '127.0.0.1',
            'details' => ['name' => $project1->name],
            'created_at' => Carbon::now()->subDays(20),
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'search_engine_property_connected',
            'resource_type' => 'SearchEngineProperty',
            'resource_id' => (string) $bingProperty->id,
            'ip_address' => '127.0.0.1',
            'details' => ['provider' => 'bing_webmaster', 'property_url' => 'https://acme.io/'],
            'created_at' => Carbon::now()->subDays(19),
        ]);

        // 9. Pre-generated SEO Reports
        Report::create([
            'user_id' => $admin->id,
            'project_id' => $project1->id,
            'campaign_id' => $campaign1->id,
            'title' => 'Q1 Comprehensive Backlink Health & Indexation Audit',
            'type' => 'campaign_summary',
            'format' => 'json',
            'summary_metrics' => [
                'total_backlinks' => 9,
                'live_links' => 6,
                'lost_links' => 3,
                'indexed_count' => 4,
                'index_rate' => 44.4,
                'crawl_status_summary' => ['CRAWLED' => 6, 'ERROR' => 2, 'DISCOVERED' => 1],
            ],
            'created_at' => Carbon::now()->subDays(1),
        ]);

        Report::create([
            'user_id' => $admin->id,
            'project_id' => $project1->id,
            'campaign_id' => $campaign1->id,
            'title' => 'Lost Backlink Alerts & 404 Degradation Report',
            'type' => 'lost_backlinks',
            'format' => 'json',
            'summary_metrics' => [
                'total_lost' => 3,
                'immediate_actions_recommended' => [
                    'Re-reach out to devdigest.net regarding dropped editorial mention',
                    'Check oldstartuparchive.org broken URL',
                ],
            ],
            'created_at' => Carbon::now()->subHours(12),
        ]);
    }
}

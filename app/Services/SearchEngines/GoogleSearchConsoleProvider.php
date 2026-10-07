<?php

namespace App\Services\SearchEngines;

use App\Contracts\IndexStatusProviderInterface;
use App\Contracts\SearchEngineProviderInterface;
use App\Models\Backlink;
use App\Models\SearchEngineProperty;
use App\Services\Logging\ExternalApiLoggerService;
use Exception;
use Illuminate\Support\Facades\Http;

class GoogleSearchConsoleProvider implements SearchEngineProviderInterface, IndexStatusProviderInterface
{
    public function __construct(
        protected ExternalApiLoggerService $apiLogger
    ) {}

    public function getProviderName(): string
    {
        return 'google_search_console';
    }

    public function validateProperty(SearchEngineProperty $property): array
    {
        $credentials = $property->encrypted_credentials ?? [];
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'No OAuth access token available for Google Search Console property.',
            ];
        }

        $endpoint = 'https://www.googleapis.com/webmasters/v3/sites';
        $startTime = microtime(true);

        try {
            $response = Http::withToken($accessToken)->get($endpoint);
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            $this->apiLogger->log(
                provider: 'google_search_console',
                endpointCategory: 'auth',
                url: $endpoint,
                httpMethod: 'GET',
                responseCode: $response->status(),
                latencyMs: $latency,
                rawResponse: $response->json(),
                userId: $property->user_id
            );

            if ($response->successful()) {
                $sites = $response->json('siteEntry', []);
                $propertyUrl = $property->property_url;
                $isFound = false;

                foreach ($sites as $site) {
                    if (str_contains($site['siteUrl'] ?? '', $propertyUrl) || str_contains($propertyUrl, $site['siteUrl'] ?? '')) {
                        $isFound = true;
                        break;
                    }
                }

                $property->update([
                    'is_authorized' => $isFound,
                    'authorization_status' => $isFound ? 'authorized' : 'unverified_in_gsc',
                    'authorized_at' => $isFound ? now() : null,
                ]);

                return [
                    'success' => $isFound,
                    'message' => $isFound ? 'Property verified in Google Search Console.' : 'Domain not verified under authenticated Google account.',
                ];
            }

            return ['success' => false, 'message' => 'GSC authorization request failed: ' . $response->body()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'GSC error: ' . $e->getMessage()];
        }
    }

    /**
     * Google Search Console URL Inspection API.
     * Enforces authorized property check.
     */
    public function inspectIndexStatus(Backlink $backlink): array
    {
        $host = parse_url($backlink->target_url, PHP_URL_HOST);
        $property = SearchEngineProperty::where('provider', 'google_search_console')
            ->where('is_authorized', true)
            ->where('property_url', 'like', "%{$host}%")
            ->first();

        if (!$property) {
            return [
                'success' => false,
                'index_status' => 'UNKNOWN',
                'message' => 'GSC inspection requires an authorized Google Search Console property. Principle #5 enforced.',
            ];
        }

        if (!$property->hasQuotaAvailable()) {
            return [
                'success' => false,
                'index_status' => $backlink->index_status,
                'message' => 'Daily Google URL Inspection API quota reached (2,000/day limit).',
            ];
        }

        $credentials = $property->encrypted_credentials ?? [];
        $accessToken = $credentials['access_token'] ?? null;
        $endpoint = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';

        $payload = [
            'inspectionUrl' => $backlink->target_url,
            'siteUrl' => $property->property_url,
            'languageCode' => 'en-US',
        ];

        $startTime = microtime(true);
        try {
            $response = Http::withToken($accessToken)->timeout(12)->post($endpoint, $payload);
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            $this->apiLogger->log(
                provider: 'google_search_console',
                endpointCategory: 'inspection',
                url: $endpoint,
                httpMethod: 'POST',
                responseCode: $response->status(),
                latencyMs: $latency,
                rawRequest: $payload,
                rawResponse: $response->json(),
                errorMessage: $response->successful() ? null : $response->body(),
                userId: $property->user_id
            );

            $property->incrementQuotaUsage(1);

            if ($response->successful()) {
                $inspectionResult = $response->json('inspectionResult.indexStatusResult', []);
                $coverageState = $inspectionResult['coverageState'] ?? 'Unknown';
                $verdict = $inspectionResult['verdict'] ?? 'NEUTRAL';
                $indexingState = $inspectionResult['indexingState'] ?? 'INDEXING_UNSPECIFIED';
                $lastCrawlTime = $inspectionResult['lastCrawlTime'] ?? null;
                $pageFetchState = $inspectionResult['pageFetchState'] ?? 'SUCCESSFUL';
                $robotsTxtState = $inspectionResult['robotsTxtState'] ?? 'ALLOWED';

                // Distinguish exact status
                $isIndexed = ($verdict === 'PASS' && str_contains(strtolower($coverageState), 'indexed'));
                $indexStatus = $isIndexed ? 'INDEXED' : (str_contains(strtolower($coverageState), 'crawled') ? 'CRAWLED' : 'NOT_INDEXED');

                $backlink->update([
                    'is_indexed' => $isIndexed,
                    'index_status' => $indexStatus,
                    'crawl_status' => ($pageFetchState === 'SUCCESSFUL') ? 'CRAWLED' : 'ERROR',
                    'last_indexed_check_at' => now(),
                    'last_crawled_at' => $lastCrawlTime ? date('Y-m-d H:i:s', strtotime($lastCrawlTime)) : $backlink->last_crawled_at,
                    'metadata' => array_merge($backlink->metadata ?? [], [
                        'gsc_inspection' => [
                            'verdict' => $verdict,
                            'coverage_state' => $coverageState,
                            'robots_txt_state' => $robotsTxtState,
                            'page_fetch_state' => $pageFetchState,
                            'last_crawl_time' => $lastCrawlTime,
                        ]
                    ]),
                ]);

                return [
                    'success' => true,
                    'is_indexed' => $isIndexed,
                    'index_status' => $indexStatus,
                    'verdict' => $verdict,
                    'coverage_state' => $coverageState,
                    'page_fetch_state' => $pageFetchState,
                    'robots_txt_state' => $robotsTxtState,
                    'last_crawl_time' => $lastCrawlTime,
                ];
            }

            return [
                'success' => false,
                'index_status' => $backlink->index_status,
                'message' => 'GSC inspection failed: ' . $response->body(),
            ];
        } catch (Exception $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $this->apiLogger->log(
                provider: 'google_search_console',
                endpointCategory: 'inspection',
                url: $endpoint,
                httpMethod: 'POST',
                responseCode: 500,
                latencyMs: $latency,
                errorMessage: $e->getMessage(),
                userId: $property->user_id
            );

            return [
                'success' => false,
                'index_status' => $backlink->index_status,
                'message' => 'GSC connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Google Indexing API - RESTRICTED strictly to JobPosting / BroadcastEvent page types.
     * Principle #6 enforced.
     */
    public function submitIndexingApi(string $url, string $pageType, SearchEngineProperty $property): array
    {
        $allowedTypes = ['JobPosting', 'BroadcastEvent'];
        if (!in_array($pageType, $allowedTypes, true)) {
            throw new Exception("Principle #6 Enforced: Google Indexing API is strictly limited by Google to JobPosting and BroadcastEvent structured data pages. General web URLs cannot be submitted via Indexing API.");
        }

        // Endpoint: https://indexing.googleapis.com/v3/urlNotifications:publish
        return [
            'success' => true,
            'eligible' => true,
            'page_type' => $pageType,
            'message' => 'Validated eligible page type for Google Indexing API.',
        ];
    }

    public function getQuotaInfo(SearchEngineProperty $property): array
    {
        return [
            'provider' => 'google_search_console',
            'daily_limit' => $property->quota_daily ?: 2000,
            'used_today' => $property->quota_used_today,
            'remaining' => max(0, ($property->quota_daily ?: 2000) - $property->quota_used_today),
        ];
    }
}

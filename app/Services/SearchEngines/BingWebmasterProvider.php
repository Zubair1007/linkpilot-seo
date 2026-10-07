<?php

namespace App\Services\SearchEngines;

use App\Contracts\DiscoveryProviderInterface;
use App\Contracts\SearchEngineProviderInterface;
use App\Models\Backlink;
use App\Models\DiscoveryJob;
use App\Models\SearchEngineProperty;
use App\Services\Logging\ExternalApiLoggerService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BingWebmasterProvider implements DiscoveryProviderInterface, SearchEngineProviderInterface
{
    public function __construct(
        protected ExternalApiLoggerService $apiLogger
    ) {}

    public function getProviderName(): string
    {
        return 'bing_webmaster';
    }

    /**
     * Bing requires authorized site property.
     */
    public function isAuthorizedForUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        return SearchEngineProperty::where('provider', 'bing_webmaster')
            ->where('is_authorized', true)
            ->where(function ($query) use ($host) {
                $query->where('property_url', 'like', "%{$host}%");
            })
            ->exists();
    }

    public function validateProperty(SearchEngineProperty $property): array
    {
        $startTime = microtime(true);
        $credentials = $property->encrypted_credentials ?? [];
        $apiKey = $credentials['api_key'] ?? null;

        if (!$apiKey) {
            return [
                'success' => false,
                'message' => 'Missing Bing Webmaster API key.',
            ];
        }

        // Endpoint: https://ssl.bing.com/webmaster/api.svc/json/GetUserSites?apikey={apiKey}
        $endpoint = 'https://ssl.bing.com/webmaster/api.svc/json/GetUserSites';

        try {
            $response = Http::timeout(10)->get($endpoint, [
                'apikey' => $apiKey,
            ]);

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $this->apiLogger->log(
                provider: 'bing_webmaster',
                endpointCategory: 'auth',
                url: $endpoint,
                httpMethod: 'GET',
                responseCode: $response->status(),
                latencyMs: $latency,
                rawRequest: ['endpoint' => $endpoint, 'site' => $property->property_url],
                rawResponse: $response->json(),
                errorMessage: $response->successful() ? null : $response->body(),
                userId: $property->user_id
            );

            if ($response->successful()) {
                $property->update([
                    'is_authorized' => true,
                    'authorization_status' => 'authorized',
                    'authorized_at' => now(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Bing Webmaster property authorized successfully.',
                    'sites' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Bing API authorization failed with HTTP ' . $response->status(),
            ];
        } catch (Exception $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $this->apiLogger->log(
                provider: 'bing_webmaster',
                endpointCategory: 'auth',
                url: $endpoint,
                httpMethod: 'GET',
                responseCode: 500,
                latencyMs: $latency,
                errorMessage: $e->getMessage(),
                userId: $property->user_id
            );

            return [
                'success' => false,
                'message' => 'Bing connection error: ' . $e->getMessage(),
            ];
        }
    }

    public function submitUrl(Backlink $backlink, ?DiscoveryJob $job = null): array
    {
        $property = SearchEngineProperty::where('provider', 'bing_webmaster')
            ->where('is_authorized', true)
            ->where(function ($q) use ($backlink) {
                $host = parse_url($backlink->target_url, PHP_URL_HOST);
                $q->where('property_url', 'like', "%{$host}%");
            })->first();

        if (!$property) {
            return [
                'success' => false,
                'response_code' => 403,
                'message' => 'No authorized Bing property found for this backlink domain. Principle #3 enforced.',
            ];
        }

        if (!$property->hasQuotaAvailable()) {
            return [
                'success' => false,
                'response_code' => 429,
                'message' => 'Daily Bing Webmaster submission quota exceeded. Principle #7 enforced.',
            ];
        }

        $credentials = $property->encrypted_credentials ?? [];
        $apiKey = $credentials['api_key'] ?? '';
        $endpoint = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrl';

        $startTime = microtime(true);
        try {
            $payload = [
                'siteUrl' => $property->property_url,
                'url' => $backlink->target_url,
            ];

            $response = Http::timeout(10)->post("{$endpoint}?apikey={$apiKey}", $payload);
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            $this->apiLogger->log(
                provider: 'bing_webmaster',
                endpointCategory: 'submission',
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

            $backlink->update([
                'discovery_status' => 'SUBMITTED',
            ]);

            return [
                'success' => $response->successful(),
                'response_code' => $response->status(),
                'message' => $response->successful() ? 'URL submitted to Bing Webmaster for discovery.' : $response->body(),
            ];
        } catch (Exception $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $this->apiLogger->log(
                provider: 'bing_webmaster',
                endpointCategory: 'submission',
                url: $endpoint,
                httpMethod: 'POST',
                responseCode: 500,
                latencyMs: $latency,
                errorMessage: $e->getMessage(),
                userId: $property->user_id
            );

            return [
                'success' => false,
                'response_code' => 500,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function batchSubmit(array $backlinks): array
    {
        $results = [];
        foreach ($backlinks as $backlink) {
            $results[] = $this->submitUrl($backlink);
        }
        return $results;
    }

    public function getQuotaInfo(SearchEngineProperty $property): array
    {
        return [
            'provider' => 'bing_webmaster',
            'daily_limit' => $property->quota_daily,
            'used_today' => $property->quota_used_today,
            'remaining' => max(0, $property->quota_daily - $property->quota_used_today),
        ];
    }
}

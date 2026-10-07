<?php

namespace App\Services\SearchEngines;

use App\Contracts\DiscoveryProviderInterface;
use App\Models\Backlink;
use App\Models\DiscoveryJob;
use App\Models\SearchEngineProperty;
use App\Services\Logging\ExternalApiLoggerService;
use Exception;
use Illuminate\Support\Facades\Http;

class IndexNowProvider implements DiscoveryProviderInterface
{
    public function __construct(
        protected ExternalApiLoggerService $apiLogger
    ) {}

    public function getProviderName(): string
    {
        return 'indexnow';
    }

    public function isAuthorizedForUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        return SearchEngineProperty::where('provider', 'indexnow')
            ->where('is_authorized', true)
            ->where('property_url', 'like', "%{$host}%")
            ->exists();
    }

    public function submitUrl(Backlink $backlink, ?DiscoveryJob $job = null): array
    {
        return $this->batchSubmit([$backlink]);
    }

    public function batchSubmit(array $backlinks): array
    {
        if (empty($backlinks)) {
            return ['success' => true, 'count' => 0, 'message' => 'No backlinks provided.'];
        }

        // Group backlinks by authorized property host
        $first = $backlinks[0];
        $host = parse_url($first->target_url, PHP_URL_HOST);

        $property = SearchEngineProperty::where('provider', 'indexnow')
            ->where('is_authorized', true)
            ->where('property_url', 'like', "%{$host}%")
            ->first();

        if (!$property) {
            return [
                'success' => false,
                'response_code' => 403,
                'message' => "IndexNow authorization required for host [{$host}]. Principle #4 enforced.",
            ];
        }

        $credentials = $property->encrypted_credentials ?? [];
        $key = $credentials['key'] ?? '';
        $keyLocation = $credentials['key_location'] ?? null;

        $urlList = array_map(fn($b) => $b->target_url, $backlinks);
        $payload = [
            'host' => $host,
            'key' => $key,
            'urlList' => array_values(array_unique($urlList)),
        ];

        if ($keyLocation) {
            $payload['keyLocation'] = $keyLocation;
        }

        $endpoint = 'https://api.indexnow.org/indexnow';
        $startTime = microtime(true);

        try {
            $response = Http::timeout(10)->post($endpoint, $payload);
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            $this->apiLogger->log(
                provider: 'indexnow',
                endpointCategory: 'submission',
                url: $endpoint,
                httpMethod: 'POST',
                responseCode: $response->status(),
                latencyMs: $latency,
                rawRequest: $payload,
                rawResponse: ['status' => $response->status(), 'body' => $response->body()],
                errorMessage: $response->successful() ? null : $response->body(),
                userId: $property->user_id
            );

            // IndexNow returns 200 (OK) or 202 (Accepted)
            $success = in_array($response->status(), [200, 202], true);

            if ($success) {
                foreach ($backlinks as $b) {
                    $b->update(['discovery_status' => 'SUBMITTED']);
                }
            }

            return [
                'success' => $success,
                'response_code' => $response->status(),
                'submitted_count' => count($urlList),
                'message' => $success ? 'Submitted to IndexNow protocol successfully.' : 'IndexNow error: ' . $response->body(),
            ];
        } catch (Exception $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $this->apiLogger->log(
                provider: 'indexnow',
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
                'message' => 'IndexNow connection error: ' . $e->getMessage(),
            ];
        }
    }
}

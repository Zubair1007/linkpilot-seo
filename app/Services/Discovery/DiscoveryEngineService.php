<?php

namespace App\Services\Discovery\Services;

namespace App\Services\Discovery;

use App\Contracts\DiscoveryProviderInterface;
use App\Models\Backlink;
use App\Models\DiscoveryJob;
use App\Services\SearchEngines\BingWebmasterProvider;
use App\Services\SearchEngines\IndexNowProvider;
use Exception;
use Illuminate\Support\Facades\Log;

class DiscoveryEngineService
{
    /**
     * Registered discovery providers map.
     * @var array<string, DiscoveryProviderInterface>
     */
    protected array $providers = [];

    public function __construct(
        BingWebmasterProvider $bingProvider,
        IndexNowProvider $indexNowProvider
    ) {
        $this->registerProvider($bingProvider);
        $this->registerProvider($indexNowProvider);
    }

    public function registerProvider(DiscoveryProviderInterface $provider): void
    {
        $this->providers[$provider->getProviderName()] = $provider;
    }

    public function getProvider(string $name): ?DiscoveryProviderInterface
    {
        return $this->providers[$name] ?? null;
    }

    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Dispatch discovery process for a backlink with full job audit logging.
     */
    public function dispatchDiscovery(Backlink $backlink, string $providerName, string $method = 'submission'): DiscoveryJob
    {
        $provider = $this->getProvider($providerName);
        if (!$provider) {
            throw new Exception("Discovery provider [{$providerName}] is not registered.");
        }

        // Free-tier conservative quota guardrail
        $dailyLimit = (int) config('linkpilot.limits.daily_provider_limit', 100);
        $todayCount = DiscoveryJob::where('provider', $providerName)
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= $dailyLimit) {
            throw new Exception("Daily limit of {$dailyLimit} discovery submissions reached for provider [{$providerName}] (Free-tier guardrail: DAILY_PROVIDER_LIMIT).");
        }

        $job = DiscoveryJob::create([
            'backlink_id' => $backlink->id,
            'provider' => $providerName,
            'method' => $method,
            'status' => 'pending',
            'attempt' => 1,
            'scheduled_at' => now(),
        ]);

        $job->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $provider->submitUrl($backlink, $job);

            $job->update([
                'status' => $result['success'] ? 'completed' : 'failed',
                'completed_at' => now(),
                'response_code' => $result['response_code'] ?? ($result['success'] ? 200 : 400),
                'response_message' => $result['message'] ?? 'Processed',
                'payload_metadata' => $result,
            ]);

            return $job;
        } catch (Exception $e) {
            $job->update([
                'status' => 'failed',
                'completed_at' => now(),
                'response_code' => 500,
                'response_message' => $e->getMessage(),
            ]);

            return $job;
        }
    }
}

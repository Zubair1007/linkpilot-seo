<?php

namespace App\Jobs;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public Campaign $campaign
    ) {}

    public function handle(): void
    {
        $limit = (int) config('linkpilot.limits.max_url_checks_per_run', 25);
        $backlinks = $this->campaign->backlinks()
            ->where('is_live', true)
            ->limit($limit)
            ->get();

        $delaySeconds = 0;
        foreach ($backlinks as $backlink) {
            // Space out jobs with small throttling delay to avoid aggressive burst requests
            VerifyBacklinkJob::dispatch($backlink)->delay(now()->addSeconds($delaySeconds));
            $delaySeconds += 2;
        }

        // Calculate next run according to check_frequency ('24h', '72h', '7d', '14d', '30d')
        $next = match ($this->campaign->check_frequency) {
            '72h' => Carbon::now()->addHours(72),
            '7d' => Carbon::now()->addDays(7),
            '14d' => Carbon::now()->addDays(14),
            '30d' => Carbon::now()->addDays(30),
            default => Carbon::now()->addHours(24),
        };

        $this->campaign->update([
            'last_run_at' => now(),
            'next_run_at' => $next,
        ]);
    }
}

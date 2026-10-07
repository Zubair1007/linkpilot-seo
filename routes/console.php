<?php

use App\Jobs\ProcessCampaignJob;
use App\Jobs\VerifyBacklinkJob;
use App\Models\Backlink;
use App\Models\Campaign;
use App\Models\SearchEngineProperty;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule periodic campaigns
Schedule::call(function () {
    $now = now();
    $dueCampaigns = Campaign::where('is_active', true)
        ->where(function ($query) use ($now) {
            $query->whereNull('next_run_at')
                ->orWhere('next_run_at', '<=', $now);
        })
        ->get();

    foreach ($dueCampaigns as $campaign) {
        ProcessCampaignJob::dispatch($campaign);
    }
})->hourly()->name('linkpilot:run-campaigns');

// Schedule retry engine processing
Schedule::call(function () {
    $now = now();
    $retryBacklinks = Backlink::whereNotNull('next_retry_at')
        ->where('next_retry_at', '<=', $now)
        ->get();

    foreach ($retryBacklinks as $backlink) {
        VerifyBacklinkJob::dispatch($backlink);
    }
})->everyFifteenMinutes()->name('linkpilot:run-retries');

// Reset daily API quotas at midnight
Schedule::call(function () {
    SearchEngineProperty::query()->update([
        'quota_used_today' => 0,
        'last_quota_reset_at' => now(),
    ]);
})->dailyAt('00:00')->name('linkpilot:reset-daily-quotas');

// Artisan command for manual backlink check
Artisan::command('linkpilot:verify {id : Backlink ID}', function ($id) {
    $backlink = Backlink::find($id);
    if (!$backlink) {
        $this->error("Backlink #{$id} not found.");
        return;
    }
    $this->info("Dispatching verification for Backlink #{$id} ({$backlink->source_url})...");
    app(\App\Services\Verification\BacklinkVerificationService::class)->verifyBacklink($backlink);
    $this->info("Verification completed. Is Live: " . ($backlink->fresh()->is_live ? 'YES' : 'NO'));
})->purpose('Verify a single backlink immediately');

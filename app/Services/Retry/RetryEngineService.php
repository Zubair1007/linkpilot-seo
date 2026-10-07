<?php

namespace App\Services\Retry;

use App\Models\Backlink;
use Carbon\Carbon;

class RetryEngineService
{
    protected const DEFAULT_MAX_RETRIES = 5;
    protected const BASE_DELAY_MINUTES = 15; // 15m, 30m, 60m, 120m, 240m

    // Permanent failure status codes that should NOT be retried repeatedly
    protected const PERMANENT_ERROR_CODES = [404, 410, 451];

    /**
     * Determine if a backlink can and should be scheduled for retry.
     */
    public function canRetry(Backlink $backlink): bool
    {
        // Don't retry if permanently invalid status code
        if (in_array($backlink->http_status, self::PERMANENT_ERROR_CODES, true)) {
            return false;
        }

        // Don't retry if reached maximum retry attempts
        $maxRetries = $backlink->max_retries ?: (int) config('linkpilot.limits.max_retries', self::DEFAULT_MAX_RETRIES);
        if ($backlink->retry_count >= $maxRetries) {
            return false;
        }

        return true;
    }

    /**
     * Calculate exponential backoff and update backlink schedule.
     */
    public function scheduleRetry(Backlink $backlink, ?string $reason = null): ?Carbon
    {
        if (!$this->canRetry($backlink)) {
            $backlink->update([
                'next_retry_at' => null,
                'last_error' => $reason ? "Max retries reached or permanent error: {$reason}" : 'Permanent failure or max retries exceeded',
            ]);
            return null;
        }

        $nextAttempt = $backlink->retry_count + 1;
        // Exponential backoff: base_delay * (2 ^ (nextAttempt - 1))
        $delayMinutes = self::BASE_DELAY_MINUTES * (2 ** ($nextAttempt - 1));
        // Cap max delay to 24 hours (1440 minutes)
        $delayMinutes = min($delayMinutes, 1440);

        $nextRetryAt = Carbon::now()->addMinutes($delayMinutes);

        $backlink->update([
            'retry_count' => $nextAttempt,
            'next_retry_at' => $nextRetryAt,
            'last_error' => $reason,
        ]);

        return $nextRetryAt;
    }

    /**
     * Reset retries when a link is verified healthy.
     */
    public function resetRetries(Backlink $backlink): void
    {
        $backlink->update([
            'retry_count' => 0,
            'next_retry_at' => null,
            'last_error' => null,
        ]);
    }
}

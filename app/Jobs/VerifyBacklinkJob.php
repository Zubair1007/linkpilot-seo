<?php

namespace App\Jobs;

use App\Models\Backlink;
use App\Services\Retry\RetryEngineService;
use App\Services\Verification\BacklinkVerificationService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyBacklinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public Backlink $backlink
    ) {}

    public function handle(
        BacklinkVerificationService $verifier,
        RetryEngineService $retryEngine
    ): void {
        try {
            $result = $verifier->verifyBacklink($this->backlink);
            if ($result['analysis']['passed']) {
                $retryEngine->resetRetries($this->backlink);
            }
        } catch (Exception $e) {
            Log::warning("VerifyBacklinkJob failed for Backlink #{$this->backlink->id}: " . $e->getMessage());

            if ($retryEngine->canRetry($this->backlink)) {
                $retryEngine->scheduleRetry($this->backlink, $e->getMessage());
            }

            throw $e;
        }
    }
}

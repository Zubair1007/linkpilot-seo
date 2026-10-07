<?php

namespace Tests\Unit;

use App\Models\Backlink;
use App\Services\Retry\RetryEngineService;
use PHPUnit\Framework\TestCase;

class RetryEngineTest extends TestCase
{
    protected RetryEngineService $retryEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->retryEngine = new RetryEngineService();
    }

    public function test_will_not_retry_permanent_404_or_410(): void
    {
        $backlink = new Backlink(['http_status' => 404, 'retry_count' => 0, 'max_retries' => 5]);
        $this->assertFalse($this->retryEngine->canRetry($backlink));

        $backlink410 = new Backlink(['http_status' => 410, 'retry_count' => 0, 'max_retries' => 5]);
        $this->assertFalse($this->retryEngine->canRetry($backlink410));
    }

    public function test_will_not_retry_when_max_retries_exceeded(): void
    {
        $backlink = new Backlink(['http_status' => 500, 'retry_count' => 5, 'max_retries' => 5]);
        $this->assertFalse($this->retryEngine->canRetry($backlink));
    }

    public function test_allows_retry_on_transient_error(): void
    {
        $backlink = new Backlink(['http_status' => 503, 'retry_count' => 1, 'max_retries' => 5]);
        $this->assertTrue($this->retryEngine->canRetry($backlink));
    }
}

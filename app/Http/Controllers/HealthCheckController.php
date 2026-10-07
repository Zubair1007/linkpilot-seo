<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckController extends Controller
{
    /**
     * Production health-check endpoint.
     * Verifies:
     * - Application status
     * - Database connection & driver
     * - Queue configuration & pending jobs
     * - Application version
     */
    public function __invoke(): JsonResponse
    {
        $dbStatus = 'healthy';
        $dbDriver = config('database.default');
        $dbLatencyMs = null;
        $dbError = null;

        // 1. Database Connection Check
        $startTime = microtime(true);
        try {
            DB::connection()->getPdo();
            // Perform lightweight query
            DB::select('SELECT 1');
            $dbLatencyMs = (int) round((microtime(true) - $startTime) * 1000);
        } catch (Throwable $e) {
            $dbStatus = 'error';
            $dbError = $e->getMessage();
        }

        // 2. Queue Configuration Check
        $queueConnection = config('queue.default');
        $pendingJobs = null;
        $queueStatus = 'ready';

        if ($queueConnection === 'database') {
            try {
                $pendingJobs = DB::table('jobs')->count();
            } catch (Throwable) {
                $pendingJobs = null;
            }
        }

        // 3. Overall System Status
        $isHealthy = ($dbStatus === 'healthy');
        $httpCode = $isHealthy ? 200 : 503;

        $response = [
            'status' => $isHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'app_version' => config('linkpilot.version', '1.0.0'),
            'environment' => config('app.env', 'production'),
            'checks' => [
                'database' => [
                    'status' => $dbStatus,
                    'driver' => $dbDriver,
                    'latency_ms' => $dbLatencyMs,
                    'error' => $dbError,
                ],
                'queue' => [
                    'status' => $queueStatus,
                    'connection' => $queueConnection,
                    'pending_jobs' => $pendingJobs,
                ],
                'limits' => [
                    'max_urls_per_import' => config('linkpilot.limits.max_urls_per_import', 50),
                    'max_url_checks_per_run' => config('linkpilot.limits.max_url_checks_per_run', 25),
                    'max_retries' => config('linkpilot.limits.max_retries', 3),
                    'request_timeout_sec' => config('linkpilot.limits.request_timeout', 10),
                ],
            ],
        ];

        return response()->json($response, $httpCode);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DiscoveryJob;
use App\Models\ExternalApiLog;
use App\Models\HealthCheck;
use App\Services\Analysis\UrlHealthAnalyzerService;
use App\Services\Security\SsrfProtectionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckApiController extends Controller
{
    /**
     * SSRF-safe Live URL Health Analyzer tool.
     */
    public function analyzeUrl(
        Request $request,
        SsrfProtectionService $ssrfService,
        UrlHealthAnalyzerService $analyzer
    ): JsonResponse {
        $request->validate([
            'source_url' => 'required|url',
            'target_url' => 'nullable|url',
        ]);

        $sourceUrl = $request->source_url;
        $targetUrl = $request->target_url ?? 'https://example.com';

        try {
            // First run explicit SSRF safety validation
            $validatedUrl = $ssrfService->validateUrl($sourceUrl);

            // Fetch and analyze
            $fetchResult = $ssrfService->safeFetch($validatedUrl);

            $parsed = null;
            if ($fetchResult['success'] && !empty($fetchResult['body'])) {
                $parsed = $analyzer->parseHtml($fetchResult['body'], $targetUrl);
            }

            return response()->json([
                'source_url' => $sourceUrl,
                'ssrf_safe' => true,
                'fetch' => [
                    'http_status' => $fetchResult['http_status'],
                    'latency_ms' => $fetchResult['latency_ms'],
                    'final_url' => $fetchResult['final_url'],
                    'redirect_count' => $fetchResult['redirect_count'],
                    'content_type' => $fetchResult['content_type'],
                    'error' => $fetchResult['error'],
                ],
                'page_analysis' => $parsed,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'source_url' => $sourceUrl,
                'ssrf_safe' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Health check history.
     */
    public function healthChecks(Request $request): JsonResponse
    {
        $query = HealthCheck::with('backlink:id,source_url,target_url,campaign_id');
        if ($request->has('passed')) {
            $query->where('passed', filter_var($request->passed, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json($query->latest('created_at')->paginate(20));
    }

    /**
     * Discovery jobs queue inspection.
     */
    public function discoveryJobs(Request $request): JsonResponse
    {
        $query = DiscoveryJob::with('backlink:id,source_url,target_url');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * External API request logs (Principle #9).
     */
    public function apiLogs(Request $request): JsonResponse
    {
        $query = ExternalApiLog::with('user:id,name');
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }
        if ($request->filled('endpoint_category')) {
            $query->where('endpoint_category', $request->endpoint_category);
        }

        return response()->json($query->latest('created_at')->paginate(25));
    }
}

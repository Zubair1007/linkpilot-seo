<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\VerifyBacklinkJob;
use App\Models\Backlink;
use App\Models\Project;
use App\Services\Discovery\DiscoveryEngineService;
use App\Services\Verification\BacklinkVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BacklinkApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Backlink::with(['campaign:id,name', 'project:id,name']);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->has('is_live')) {
            $query->where('is_live', filter_var($request->is_live, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('crawl_status')) {
            $query->where('crawl_status', $request->crawl_status);
        }

        if ($request->filled('index_status')) {
            $query->where('index_status', $request->index_status);
        }

        if ($request->filled('link_type')) {
            $query->where('link_type', $request->link_type);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('source_url', 'like', "%{$s}%")
                    ->orWhere('target_url', 'like', "%{$s}%")
                    ->orWhere('anchor_text', 'like', "%{$s}%");
            });
        }

        return response()->json($query->latest()->paginate(25));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'campaign_id' => 'required|exists:campaigns,id',
            'source_url' => 'required|url',
            'target_url' => 'required|url',
            'anchor_text' => 'nullable|string|max:255',
            'link_type' => 'nullable|in:dofollow,nofollow,ugc,sponsored,unknown',
        ]);

        $backlink = Backlink::create(array_merge($validated, [
            'is_live' => true,
            'is_indexed' => false,
            'crawl_status' => 'PENDING',
            'index_status' => 'UNKNOWN',
            'discovery_status' => 'PENDING',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'retry_count' => 0,
            'max_retries' => 5,
        ]));

        $backlink->recordEvent('added', null, [
            'source_url' => $backlink->source_url,
            'target_url' => $backlink->target_url,
            'anchor_text' => $backlink->anchor_text,
        ], 'Manually created backlink entry.');

        return response()->json($backlink, 201);
    }

    public function show(int $id): JsonResponse
    {
        $backlink = Backlink::with(['events', 'healthChecks', 'discoveryJobs', 'campaign', 'project'])
            ->findOrFail($id);

        return response()->json($backlink);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $backlink = Backlink::findOrFail($id);

        $validated = $request->validate([
            'target_url' => 'sometimes|url',
            'anchor_text' => 'nullable|string|max:255',
            'link_type' => 'sometimes|string',
            'is_live' => 'sometimes|boolean',
            'max_retries' => 'sometimes|integer|min:1|max:20',
        ]);

        $backlink->update($validated);

        return response()->json($backlink);
    }

    public function destroy(int $id): JsonResponse
    {
        $backlink = Backlink::findOrFail($id);
        $backlink->delete();

        return response()->json(['message' => 'Backlink deleted.']);
    }

    /**
     * Run immediate synchronous or queued verification for a backlink.
     */
    public function verify(Request $request, int $id, BacklinkVerificationService $verifier): JsonResponse
    {
        $backlink = Backlink::findOrFail($id);

        if ($request->boolean('async')) {
            VerifyBacklinkJob::dispatch($backlink);
            return response()->json(['message' => 'Verification job dispatched to background queue.']);
        }

        $result = $verifier->verifyBacklink($backlink);

        return response()->json([
            'message' => 'Verification completed.',
            'result' => $result,
        ]);
    }

    /**
     * Dispatch discovery job for this backlink.
     */
    public function submitDiscovery(Request $request, int $id, DiscoveryEngineService $discoveryEngine): JsonResponse
    {
        $backlink = Backlink::findOrFail($id);
        $provider = $request->input('provider', 'indexnow'); // 'indexnow' or 'bing_webmaster'

        $job = $discoveryEngine->dispatchDiscovery($backlink, $provider);

        return response()->json([
            'message' => 'Discovery submission dispatched.',
            'job' => $job,
        ]);
    }

    /**
     * Fetch Third-Party SEO Authority Metrics (Moz DA/PA, Ahrefs DR, SEMrush AS).
     */
    public function fetchMetrics(int $id, \App\Services\Metrics\SeoMetricsProviderService $metricsService): JsonResponse
    {
        $backlink = Backlink::findOrFail($id);
        $result = $metricsService->fetchMetricsForBacklink($backlink);

        return response()->json([
            'message' => 'SEO authority metrics fetched successfully.',
            'result' => $result,
            'backlink' => $backlink->fresh(),
        ]);
    }
}

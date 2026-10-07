<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCampaignJob;
use App\Models\Backlink;
use App\Models\BacklinkEvent;
use App\Models\Campaign;
use App\Models\DiscoveryJob;
use App\Models\Project;
use App\Services\Import\BacklinkImportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Campaign::with('project:id,name,target_domain')->withCount('backlinks');

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'check_frequency' => 'required|in:24h,72h,7d,14d,30d',
        ]);

        $project = Project::findOrFail($validated['project_id']);
        if (!$request->user()->isAdmin() && $project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $nextRun = match ($validated['check_frequency']) {
            '72h' => Carbon::now()->addHours(72),
            '7d' => Carbon::now()->addDays(7),
            '14d' => Carbon::now()->addDays(14),
            '30d' => Carbon::now()->addDays(30),
            default => Carbon::now()->addHours(24),
        };

        $campaign = Campaign::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'check_frequency' => $validated['check_frequency'],
            'is_active' => true,
            'next_run_at' => $nextRun,
        ]);

        return response()->json($campaign->load('project'), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::with('project')->findOrFail($id);
        return response()->json($campaign);
    }

    /**
     * Comprehensive Campaign Status summary.
     */
    public function status(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::with('project')->findOrFail($id);

        $backlinks = $campaign->backlinks();
        $totalUrls = $backlinks->count();
        $liveUrls = (clone $backlinks)->where('is_live', true)->count();
        $lostUrls = (clone $backlinks)->where('is_live', false)->count();

        // Crawl statuses
        $crawlStatusCounts = (clone $backlinks)
            ->selectRaw('crawl_status, count(*) as count')
            ->groupBy('crawl_status')
            ->pluck('count', 'crawl_status');

        // Index statuses
        $indexStatusCounts = (clone $backlinks)
            ->selectRaw('index_status, count(*) as count')
            ->groupBy('index_status')
            ->pluck('count', 'index_status');

        $indexedCount = $indexStatusCounts['INDEXED'] ?? 0;
        $indexRate = $totalUrls > 0 ? round(($indexedCount / $totalUrls) * 100, 1) : 0;

        $failedJobs = DiscoveryJob::whereHas('backlink', fn($q) => $q->where('campaign_id', $campaign->id))
            ->where('status', 'failed')
            ->count();

        $providerActivity = DiscoveryJob::whereHas('backlink', fn($q) => $q->where('campaign_id', $campaign->id))
            ->selectRaw('provider, status, count(*) as count')
            ->groupBy('provider', 'status')
            ->get();

        $timeline = BacklinkEvent::whereHas('backlink', fn($q) => $q->where('campaign_id', $campaign->id))
            ->with('backlink:id,source_url,target_url')
            ->latest('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'campaign' => $campaign,
            'metrics' => [
                'total_urls' => $totalUrls,
                'live_urls' => $liveUrls,
                'lost_urls' => $lostUrls,
                'index_rate' => $indexRate,
                'failed_jobs' => $failedJobs,
            ],
            'crawl_status' => $crawlStatusCounts,
            'index_status' => $indexStatusCounts,
            'provider_activity' => $providerActivity,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Trigger immediate background check for this campaign.
     */
    public function triggerRun(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);
        ProcessCampaignJob::dispatch($campaign);

        return response()->json([
            'message' => "Campaign verification jobs dispatched to queue for [{$campaign->name}].",
        ]);
    }

    /**
     * Import backlinks into this campaign.
     */
    public function import(Request $request, int $id, BacklinkImportService $importer): JsonResponse
    {
        $campaign = Campaign::with('project')->findOrFail($id);

        $defaultTarget = 'https://' . $campaign->project->target_domain;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());
            $result = $importer->importFromCsv($content, $campaign, $defaultTarget);
        } elseif ($request->filled('raw_text')) {
            $result = $importer->importFromText($request->raw_text, $campaign, $defaultTarget);
        } else {
            return response()->json(['message' => 'Please provide a file or raw_text'], 422);
        }

        return response()->json($result);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted successfully.']);
    }
}

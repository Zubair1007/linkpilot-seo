<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Backlink;
use App\Models\BacklinkEvent;
use App\Models\Campaign;
use App\Models\DiscoveryJob;
use App\Models\ExternalApiLog;
use App\Models\HealthCheck;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $projectQuery = Project::query();
        if ($userId && !$request->user()->isAdmin()) {
            $projectQuery->where('user_id', $userId);
        }
        $projectIds = $projectQuery->pluck('id');

        $totalProjects = $projectIds->count();
        $totalCampaigns = Campaign::whereIn('project_id', $projectIds)->count();
        $totalBacklinks = Backlink::whereIn('project_id', $projectIds)->count();

        $liveBacklinks = Backlink::whereIn('project_id', $projectIds)->where('is_live', true)->count();
        $lostBacklinks = Backlink::whereIn('project_id', $projectIds)->where('is_live', false)->count();

        $crawledUrls = Backlink::whereIn('project_id', $projectIds)->where('crawl_status', 'CRAWLED')->count();
        $indexedUrls = Backlink::whereIn('project_id', $projectIds)->where('index_status', 'INDEXED')->count();
        $notIndexedUrls = Backlink::whereIn('project_id', $projectIds)->where('index_status', 'NOT_INDEXED')->count();
        $urlsChecked = HealthCheck::whereHas('backlink', fn($q) => $q->whereIn('project_id', $projectIds))->count();

        $indexRate = $totalBacklinks > 0 ? round(($indexedUrls / $totalBacklinks) * 100, 1) : 0;
        $failedChecks = HealthCheck::whereHas('backlink', fn($q) => $q->whereIn('project_id', $projectIds))->where('passed', false)->count();
        $pendingJobs = DiscoveryJob::whereHas('backlink', fn($q) => $q->whereIn('project_id', $projectIds))->where('status', 'pending')->count();

        $apiUsage = ExternalApiLog::select('provider', DB::raw('count(*) as count'), DB::raw('avg(latency_ms) as avg_latency'))
            ->groupBy('provider')
            ->get();

        $recentEvents = BacklinkEvent::with('backlink:id,source_url,target_url,project_id')
            ->whereHas('backlink', fn($q) => $q->whereIn('project_id', $projectIds))
            ->latest('created_at')
            ->limit(10)
            ->get();

        $recentAudits = AuditLog::with('user:id,name')
            ->latest('created_at')
            ->limit(8)
            ->get();

        return response()->json([
            'metrics' => [
                'total_projects' => $totalProjects,
                'total_campaigns' => $totalCampaigns,
                'total_backlinks' => $totalBacklinks,
                'live_backlinks' => $liveBacklinks,
                'lost_backlinks' => $lostBacklinks,
                'urls_checked' => $urlsChecked,
                'crawled_urls' => $crawledUrls,
                'indexed_urls' => $indexedUrls,
                'not_indexed_urls' => $notIndexedUrls,
                'index_rate' => $indexRate,
                'failed_checks' => $failedChecks,
                'pending_jobs' => $pendingJobs,
            ],
            'api_usage' => $apiUsage,
            'recent_events' => $recentEvents,
            'recent_audits' => $recentAudits,
        ]);
    }
}

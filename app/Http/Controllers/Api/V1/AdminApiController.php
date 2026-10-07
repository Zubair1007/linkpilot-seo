<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Backlink;
use App\Models\DiscoveryJob;
use App\Models\ExternalApiLog;
use App\Models\Project;
use App\Models\SearchEngineProperty;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminApiController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Admin authorization required.'], 403);
        }

        $users = User::withCount(['projects', 'reports'])->paginate(20);
        return response()->json($users);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Admin authorization required.'], 403);
        }

        $user = User::findOrFail($id);
        $validated = $request->validate([
            'role' => 'sometimes|in:admin,seo_specialist,viewer',
            'status' => 'sometimes|in:active,suspended',
            'api_rate_limit' => 'sometimes|integer|min:10|max:1000',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    public function systemHealth(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Admin authorization required.'], 403);
        }

        // Check DB connection
        $dbStatus = 'healthy';
        try {
            DB::select('SELECT 1');
        } catch (\Exception $e) {
            $dbStatus = 'down: ' . $e->getMessage();
        }

        // Check failed jobs
        $failedJobsCount = DB::table('failed_jobs')->count();
        $pendingJobsCount = DB::table('jobs')->count();

        // Queue status
        $discoveryJobsTotal = DiscoveryJob::count();
        $discoveryJobsFailed = DiscoveryJob::where('status', 'failed')->count();

        // Providers overview
        $providers = SearchEngineProperty::selectRaw('provider, count(*) as total, sum(case when is_authorized = 1 then 1 else 0 end) as authorized')
            ->groupBy('provider')
            ->get();

        // Security / Audit summary
        $recentAuditCount = AuditLog::where('created_at', '>=', now()->subHours(24))->count();

        return response()->json([
            'system' => [
                'environment' => config('app.env'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => now()->toIso8601String(),
                'database' => $dbStatus,
            ],
            'queues' => [
                'pending_jobs' => $pendingJobsCount,
                'failed_jobs' => $failedJobsCount,
                'discovery_jobs_total' => $discoveryJobsTotal,
                'discovery_jobs_failed' => $discoveryJobsFailed,
            ],
            'providers' => $providers,
            'security_audits_24h' => $recentAuditCount,
        ]);
    }
}

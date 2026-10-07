<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Backlink;
use App\Models\Campaign;
use App\Models\ExternalApiLog;
use App\Models\Project;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = Report::with(['project:id,name', 'campaign:id,name'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json($reports);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'title' => 'required|string|max:255',
            'type' => 'required|in:campaign_summary,backlink_audit,lost_backlinks,index_status,api_activity',
            'format' => 'required|in:json,csv,pdf',
        ]);

        $summary = [];

        if ($validated['type'] === 'lost_backlinks') {
            $lostQuery = Backlink::where('is_live', false);
            if (!empty($validated['project_id'])) {
                $lostQuery->where('project_id', $validated['project_id']);
            }
            $summary = [
                'total_lost' => $lostQuery->count(),
                'sample' => $lostQuery->limit(10)->get(['source_url', 'target_url', 'last_seen_at', 'last_error']),
            ];
        } elseif ($validated['type'] === 'api_activity') {
            $summary = [
                'total_requests' => ExternalApiLog::count(),
                'providers' => ExternalApiLog::selectRaw('provider, count(*) as count')->groupBy('provider')->get(),
            ];
        } else {
            $blQuery = Backlink::query();
            if (!empty($validated['project_id'])) {
                $blQuery->where('project_id', $validated['project_id']);
            }
            $summary = [
                'total_backlinks' => $blQuery->count(),
                'live' => (clone $blQuery)->where('is_live', true)->count(),
                'lost' => (clone $blQuery)->where('is_live', false)->count(),
                'indexed' => (clone $blQuery)->where('index_status', 'INDEXED')->count(),
                'crawled' => (clone $blQuery)->where('crawl_status', 'CRAWLED')->count(),
            ];
        }

        $report = Report::create([
            'user_id' => $request->user()->id,
            'project_id' => $validated['project_id'] ?? null,
            'campaign_id' => $validated['campaign_id'] ?? null,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'format' => $validated['format'],
            'summary_metrics' => $summary,
        ]);

        return response()->json($report, 201);
    }

    /**
     * Export Backlinks or Reports as streaming CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $campaignId = $request->query('campaign_id');
        $projectId = $request->query('project_id');

        $query = Backlink::query();
        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="linkpilot_backlinks_export.csv"',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Source URL',
                'Target URL',
                'Anchor Text',
                'Link Type',
                'HTTP Status',
                'Is Live',
                'Crawl Status',
                'Index Status',
                'First Seen',
                'Last Verified',
            ]);

            $query->chunk(200, function ($backlinks) use ($handle) {
                foreach ($backlinks as $b) {
                    fputcsv($handle, [
                        $b->id,
                        $b->source_url,
                        $b->target_url,
                        $b->anchor_text,
                        $b->link_type,
                        $b->http_status,
                        $b->is_live ? 'YES' : 'NO',
                        $b->crawl_status,
                        $b->index_status,
                        $b->first_seen_at?->toIso8601String(),
                        $b->last_verified_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Generate branded executive client PDF report.
     */
    public function exportPdf(Request $request): Response
    {
        $projectId = $request->query('project_id');
        $campaignId = $request->query('campaign_id');

        $project = $projectId ? Project::find($projectId) : Project::first();
        $query = Backlink::query();
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        $backlinks = $query->latest()->get();
        $total = $backlinks->count();
        $live = $backlinks->where('is_live', true)->count();
        $lost = $backlinks->where('is_live', false)->values();
        $indexed = $backlinks->where('index_status', 'INDEXED')->count();
        $indexRate = $total > 0 ? round(($indexed / $total) * 100, 1) : 0;

        $metrics = [
            'total_backlinks' => $total,
            'live_backlinks' => $live,
            'lost_backlinks' => $lost->count(),
            'index_rate' => $indexRate,
        ];

        $pdf = Pdf::loadView('reports.executive_report_pdf', [
            'project' => $project,
            'backlinks' => $backlinks,
            'lostBacklinks' => $lost,
            'metrics' => $metrics,
            'reportTitle' => "LinkPilot SEO Executive Audit — " . ($project->name ?? 'Portfolio'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("linkpilot_executive_report_" . date('Y-m-d') . ".pdf");
    }
}

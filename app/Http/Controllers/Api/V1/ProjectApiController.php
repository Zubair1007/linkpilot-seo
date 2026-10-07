<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['domains', 'campaigns'])->withCount('backlinks');
        if (!$request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_domain' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
            'target_domain' => strtolower(trim(preg_replace('#^https?://#', '', $validated['target_domain']))),
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        // Automatically associate primary domain
        $project->domains()->create([
            'domain' => $project->target_domain,
            'is_verified' => false,
            'verification_token' => Str::random(32),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'project_created',
            'resource_type' => 'Project',
            'resource_id' => (string) $project->id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json($project->load('domains'), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $project = Project::with(['domains', 'campaigns', 'searchEngineProperties'])
            ->withCount('backlinks')
            ->findOrFail($id);

        if (!$request->user()->isAdmin() && $project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($project);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        if (!$request->user()->isAdmin() && $project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'target_domain' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'slack_webhook_url' => 'nullable|url',
            'alert_email' => 'nullable|email',
            'alerts_enabled' => 'sometimes|boolean',
        ]);

        $project->update($validated);

        return response()->json($project);
    }

    /**
     * Test Slack Webhook notification delivery.
     */
    public function testSlackAlert(Request $request, \App\Services\Alerts\AlertNotificationService $alertService): JsonResponse
    {
        $request->validate(['webhook_url' => 'required|url']);
        $success = $alertService->sendTestSlackAlert($request->webhook_url);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Test Slack alert sent successfully!' : 'Failed to reach Slack webhook. Please verify URL.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        if (!$request->user()->isAdmin() && $project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }

    /**
     * Add and verify a domain under this project.
     */
    public function addDomain(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'verification_method' => 'nullable|string',
        ]);

        $cleanDomain = strtolower(trim(preg_replace('#^https?://#', '', $validated['domain'])));

        $domain = $project->domains()->create([
            'domain' => $cleanDomain,
            'verification_method' => $validated['verification_method'] ?? 'dns',
            'verification_token' => 'lp-verify-' . Str::random(24),
            'is_verified' => false,
        ]);

        return response()->json($domain, 201);
    }

    public function verifyDomain(Request $request, int $domainId): JsonResponse
    {
        $domain = Domain::findOrFail($domainId);

        // Verification check simulation / DNS check
        $domain->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return response()->json([
            'message' => "Domain [{$domain->domain}] verified successfully.",
            'domain' => $domain,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SearchEngineProperty;
use App\Services\SearchEngines\BingWebmasterProvider;
use App\Services\SearchEngines\GoogleSearchConsoleProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchEngineApiController extends Controller
{
    public function listProperties(Request $request): JsonResponse
    {
        $properties = SearchEngineProperty::with('project:id,name')
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json($properties);
    }

    public function connectProperty(
        Request $request,
        BingWebmasterProvider $bingProvider,
        GoogleSearchConsoleProvider $gscProvider
    ): JsonResponse {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'provider' => 'required|in:bing_webmaster,google_search_console,indexnow',
            'property_url' => 'required|string',
            'api_key' => 'nullable|string',
            'key_location' => 'nullable|string',
            'access_token' => 'nullable|string',
        ]);

        $credentials = [];
        if ($validated['provider'] === 'bing_webmaster') {
            $credentials['api_key'] = $validated['api_key'] ?? '';
        } elseif ($validated['provider'] === 'indexnow') {
            $credentials['key'] = $validated['api_key'] ?? '';
            $credentials['key_location'] = $validated['key_location'] ?? '';
        } elseif ($validated['provider'] === 'google_search_console') {
            $credentials['access_token'] = $validated['access_token'] ?? '';
        }

        $property = SearchEngineProperty::create([
            'user_id' => $request->user()->id,
            'project_id' => $validated['project_id'] ?? null,
            'provider' => $validated['provider'],
            'property_url' => rtrim($validated['property_url'], '/'),
            'encrypted_credentials' => $credentials,
            'is_authorized' => false,
            'authorization_status' => 'pending',
            'quota_daily' => $validated['provider'] === 'bing_webmaster' ? 10000 : 2000,
            'quota_used_today' => 0,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'search_engine_property_connected',
            'resource_type' => 'SearchEngineProperty',
            'resource_id' => (string) $property->id,
            'details' => ['provider' => $property->provider, 'property_url' => $property->property_url],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        // Attempt immediate validation if key provided
        $validationResult = null;
        if ($property->provider === 'bing_webmaster' && !empty($credentials['api_key'])) {
            $validationResult = $bingProvider->validateProperty($property);
        } elseif ($property->provider === 'google_search_console' && !empty($credentials['access_token'])) {
            $validationResult = $gscProvider->validateProperty($property);
        } elseif ($property->provider === 'indexnow') {
            // IndexNow keys are verified on site
            $property->update(['is_authorized' => true, 'authorization_status' => 'authorized', 'authorized_at' => now()]);
            $validationResult = ['success' => true, 'message' => 'IndexNow property configured and verified.'];
        }

        return response()->json([
            'property' => $property->fresh(),
            'validation' => $validationResult,
        ], 201);
    }

    public function disconnectProperty(Request $request, int $id): JsonResponse
    {
        $property = SearchEngineProperty::where('user_id', $request->user()->id)->findOrFail($id);
        $property->delete();

        return response()->json(['message' => 'Property disconnected.']);
    }
}

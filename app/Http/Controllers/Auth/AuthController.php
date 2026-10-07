<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Account is suspended.'], 403);
        }

        Auth::login($user, $request->boolean('remember'));

        // Generate default API token for user
        $token = $user->createToken('web-session', ['read', 'write'])->plainTextToken;

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user_login',
            'resource_type' => 'User',
            'resource_id' => (string) $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Login successful',
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'seo_specialist',
            'status' => 'active',
            'api_rate_limit' => 60,
        ]);

        Auth::login($user);
        $token = $user->createToken('web-session', ['read', 'write'])->plainTextToken;

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user_registered',
            'resource_type' => 'User',
            'resource_id' => (string) $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Account created successfully',
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'user_logout',
                'resource_type' => 'User',
                'resource_id' => (string) $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user() ?: Auth::user(),
        ]);
    }

    /**
     * Create API key with custom name and scopes.
     */
    public function createApiKey(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Admin authorization required. SEO Specialists are not permitted to manage API keys.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'abilities' => 'array',
        ]);

        $user = $request->user();
        $abilities = $request->input('abilities', ['read', 'write']);

        $token = $user->createToken($request->name, $abilities);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'api_key_created',
            'resource_type' => 'PersonalAccessToken',
            'resource_id' => (string) $token->accessToken->id,
            'details' => ['name' => $request->name, 'abilities' => $abilities],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json([
            'token' => $token->plainTextToken,
            'name' => $request->name,
            'abilities' => $abilities,
            'id' => $token->accessToken->id,
        ], 201);
    }

    public function listApiKeys(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Admin authorization required. SEO Specialists are not permitted to view API keys.'], 403);
        }

        return response()->json([
            'keys' => $request->user()->tokens,
        ]);
    }

    public function revokeApiKey(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Admin authorization required. SEO Specialists are not permitted to revoke API keys.'], 403);
        }

        $token = $request->user()->tokens()->where('id', $id)->firstOrFail();
        $token->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'api_key_revoked',
            'resource_type' => 'PersonalAccessToken',
            'resource_id' => (string) $id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'API Key revoked.']);
    }
}

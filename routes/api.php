<?php

use App\Http\Controllers\Api\V1\AdminApiController;
use App\Http\Controllers\Api\V1\BacklinkApiController;
use App\Http\Controllers\Api\V1\CampaignApiController;
use App\Http\Controllers\Api\V1\CheckApiController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use App\Http\Controllers\Api\V1\ProjectApiController;
use App\Http\Controllers\Api\V1\ReportApiController;
use App\Http\Controllers\Api\V1\SearchEngineApiController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Health check endpoint
    Route::get('/health', \App\Http\Controllers\HealthCheckController::class);

    // Auth endpoints with rate limiting
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');

    // Authenticated API routes with rate limiting
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/tokens', [AuthController::class, 'listApiKeys']);
        Route::post('/auth/tokens', [AuthController::class, 'createApiKey']);
        Route::delete('/auth/tokens/{id}', [AuthController::class, 'revokeApiKey']);

        // Dashboard metrics
        Route::get('/dashboard', [DashboardApiController::class, 'index']);

        // Projects & Domains
        Route::apiResource('projects', ProjectApiController::class);
        Route::post('/projects/{id}/domains', [ProjectApiController::class, 'addDomain']);
        Route::post('/projects/test-slack', [ProjectApiController::class, 'testSlackAlert']);
        Route::post('/domains/{id}/verify', [ProjectApiController::class, 'verifyDomain']);

        // Campaigns
        Route::apiResource('campaigns', CampaignApiController::class);
        Route::get('/campaigns/{id}/status', [CampaignApiController::class, 'status']);
        Route::post('/campaigns/{id}/run', [CampaignApiController::class, 'triggerRun']);
        Route::post('/campaigns/{id}/import', [CampaignApiController::class, 'import']);

        // Backlinks
        Route::apiResource('backlinks', BacklinkApiController::class);
        Route::post('/backlinks/{id}/verify', [BacklinkApiController::class, 'verify']);
        Route::post('/backlinks/{id}/discover', [BacklinkApiController::class, 'submitDiscovery']);
        Route::post('/backlinks/{id}/metrics', [BacklinkApiController::class, 'fetchMetrics']);

        // URL Health Analyzer & Checks
        Route::post('/checks/analyze-url', [CheckApiController::class, 'analyzeUrl'])->middleware('throttle:checks');
        Route::get('/checks/history', [CheckApiController::class, 'healthChecks']);
        Route::get('/checks/discovery-jobs', [CheckApiController::class, 'discoveryJobs']);
        Route::get('/checks/api-logs', [CheckApiController::class, 'apiLogs']);

        // Search Engine Integrations
        Route::get('/search-engines/properties', [SearchEngineApiController::class, 'listProperties']);
        Route::post('/search-engines/connect', [SearchEngineApiController::class, 'connectProperty']);
        Route::delete('/search-engines/{id}', [SearchEngineApiController::class, 'disconnectProperty']);

        // Reports & Export
        Route::get('/reports', [ReportApiController::class, 'index']);
        Route::post('/reports/generate', [ReportApiController::class, 'generate']);
        Route::get('/reports/export-csv', [ReportApiController::class, 'exportCsv']);
        Route::get('/reports/export-pdf', [ReportApiController::class, 'exportPdf']);

        // Admin & Health (Restricted strictly to Admin role)
        Route::get('/admin/users', [AdminApiController::class, 'users']);
        Route::post('/admin/users', [AdminApiController::class, 'createUser']);
        Route::put('/admin/users/{id}', [AdminApiController::class, 'updateUser']);
        Route::delete('/admin/users/{id}', [AdminApiController::class, 'deleteUser']);
        Route::get('/admin/health', [AdminApiController::class, 'systemHealth']);
    });
});

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                if (!config('app.debug')) {
                    $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                    return response()->json([
                        'message' => $status === 500 ? 'An unexpected server error occurred. Please try again later.' : $e->getMessage(),
                        'status' => $status,
                    ], $status);
                }
            }
        });
    })->create();

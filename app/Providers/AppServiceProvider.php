<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS when served behind Cloudflare / Reverse Proxy
        if (
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
            || isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'trycloudflare.com')
            || config('app.env') === 'production'
        ) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // 1. Standard API rate limiting (60 requests/minute)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 2. Strict Auth & Login rate limiting (10 requests/minute)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // 3. Intensive URL analysis checks rate limiter (20 requests/minute)
        RateLimiter::for('checks', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}

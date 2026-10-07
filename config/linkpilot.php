<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LinkPilot SEO Platform Version & Identity
    |--------------------------------------------------------------------------
    */
    'version' => env('APP_VERSION', '1.0.0'),
    'name' => env('APP_NAME', 'LinkPilot SEO'),

    /*
    |--------------------------------------------------------------------------
    | Free-Tier Conservative Limits
    |--------------------------------------------------------------------------
    | These guardrails ensure zero-budget deployment on free platforms like
    | Render Free Web Services and Supabase Free PostgreSQL without exceeding
    | CPU, memory (512MB RAM), or database connection limits.
    */
    'limits' => [
        // Maximum URLs allowed in a single CSV/manual bulk import run
        'max_urls_per_import' => (int) env('MAX_URLS_PER_IMPORT', 50),

        // Maximum URLs analyzed per crawler check execution
        'max_url_checks_per_run' => (int) env('MAX_URL_CHECKS_PER_RUN', 25),

        // Maximum automatic retry attempts before marking a URL as permanent ERROR
        'max_retries' => (int) env('MAX_RETRIES', 3),

        // cURL HTTP request timeout in seconds
        'request_timeout' => (int) env('REQUEST_TIMEOUT', 10),

        // Maximum remote page response size accepted (default: 2MB = 2097152 bytes)
        'max_response_size' => (int) env('MAX_RESPONSE_SIZE', 2097152),

        // Maximum concurrent verification threads in queue
        'max_concurrent_checks' => (int) env('MAX_CONCURRENT_CHECKS', 3),

        // Conservative daily provider API quota guardrail
        'daily_provider_limit' => (int) env('DAILY_PROVIDER_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third-Party SEO API Credentials
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'moz_access_id' => env('MOZ_ACCESS_ID'),
        'moz_secret_key' => env('MOZ_SECRET_KEY'),
        'ahrefs_api_key' => env('AHREFS_API_KEY'),
        'semrush_api_key' => env('SEMRUSH_API_KEY'),
    ],
];

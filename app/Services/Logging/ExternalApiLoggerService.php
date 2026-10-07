<?php

namespace App\Services\Logging;

use App\Models\ExternalApiLog;
use Illuminate\Support\Facades\Auth;

class ExternalApiLoggerService
{
    /**
     * Log external search engine API requests with sanitized parameters.
     */
    public function log(
        string $provider,
        string $endpointCategory,
        string $url,
        string $httpMethod,
        ?int $responseCode,
        int $latencyMs,
        ?array $rawRequest = null,
        ?array $rawResponse = null,
        ?string $errorMessage = null,
        ?int $userId = null
    ): ExternalApiLog {
        $sanitizedRequest = $this->sanitizePayload($rawRequest);
        $sanitizedResponse = $this->sanitizePayload($rawResponse);

        return ExternalApiLog::create([
            'user_id' => $userId ?? Auth::id(),
            'provider' => $provider,
            'endpoint_category' => $endpointCategory,
            'url' => $url,
            'http_method' => strtoupper($httpMethod),
            'response_code' => $responseCode,
            'latency_ms' => $latencyMs,
            'sanitized_request' => $sanitizedRequest,
            'sanitized_response' => $sanitizedResponse,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }

    /**
     * Redact API tokens, private keys, client secrets, passwords.
     */
    protected function sanitizePayload(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $sensitiveKeys = ['key', 'token', 'secret', 'password', 'refresh_token', 'access_token', 'authorization', 'bearer', 'client_secret'];

        $sanitized = [];
        foreach ($payload as $k => $v) {
            $lowerKey = strtolower((string) $k);
            $isSensitive = false;
            foreach ($sensitiveKeys as $pattern) {
                if (str_contains($lowerKey, $pattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$k] = '[REDACTED]';
            } elseif (is_array($v)) {
                $sanitized[$k] = $this->sanitizePayload($v);
            } else {
                $sanitized[$k] = $v;
            }
        }

        return $sanitized;
    }
}

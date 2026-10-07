<?php

namespace App\Services\Security;

use Exception;
use Illuminate\Support\Facades\Log;

class SsrfProtectionService
{
    protected const MAX_REDIRECTS = 5;
    protected const MAX_RESPONSE_SIZE = 5242880; // 5 MB max response size
    protected const DEFAULT_TIMEOUT = 10; // seconds

    /**
     * Validate a URL against SSRF vulnerabilities.
     *
     * @throws Exception if URL is invalid or targets restricted/internal network.
     */
    public function validateUrl(string $url): string
    {
        $url = trim($url);
        if (empty($url)) {
            throw new Exception('URL cannot be empty.');
        }

        $parts = parse_url($url);
        if (!$parts || !isset($parts['scheme']) || !isset($parts['host'])) {
            throw new Exception('Invalid URL structure.');
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new Exception("Unsupported URL scheme [{$scheme}]. Only HTTP and HTTPS are permitted.");
        }

        $host = strtolower($parts['host']);

        // Block typical loopback / internal hostnames
        $blockedHostnames = [
            'localhost',
            '127.0.0.1',
            '::1',
            '0.0.0.0',
            'metadata.google.internal',
            '169.254.169.254',
            'instance-data',
        ];

        if (in_array($host, $blockedHostnames, true)) {
            throw new Exception("Access to host [{$host}] is blocked for security.");
        }

        if (str_ends_with($host, '.localhost') ||
            str_ends_with($host, '.local') ||
            str_ends_with($host, '.internal') ||
            str_ends_with($host, '.home') ||
            str_ends_with($host, '.lan')) {
            throw new Exception("Internal domain TLDs are blocked.");
        }

        // Port checks - block internal common ports
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $blockedPorts = [22, 25, 110, 143, 3306, 5432, 6379, 11211, 27017, 9200, 2375, 2376, 8080, 8443, 8000, 3000];
        if (isset($parts['port']) && in_array($port, $blockedPorts, true) && !in_array($port, [80, 443], true)) {
            throw new Exception("Custom port [{$port}] is restricted.");
        }

        // Resolve DNS and test IP addresses
        $ips = $this->resolveHostIps($host);
        if (empty($ips)) {
            throw new Exception("Could not resolve hostname [{$host}].");
        }

        foreach ($ips as $ip) {
            if ($this->isRestrictedIp($ip)) {
                throw new Exception("Host [{$host}] resolves to restricted private/internal IP [{$ip}]. Request blocked.");
            }
        }

        return $url;
    }

    /**
     * Resolve all IPs for host.
     */
    protected function resolveHostIps(string $host): array
    {
        $ips = [];
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (!empty($records) && is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                }
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        if (empty($ips)) {
            $ipv4 = @gethostbyname($host);
            if ($ipv4 && $ipv4 !== $host) {
                $ips[] = $ipv4;
            }
        }

        return array_unique($ips);
    }

    /**
     * Check if an IP address is within private, loopback, or reserved subnets.
     */
    public function isRestrictedIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->isRestrictedIpv4($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->isRestrictedIpv6($ip);
        }

        return true;
    }

    protected function isRestrictedIpv4(string $ip): bool
    {
        $long = ip2long($ip);
        if ($long === false) {
            return true;
        }

        // Disallow private & reserved IPv4 blocks:
        // 0.0.0.0/8
        // 10.0.0.0/8
        // 100.64.0.0/10 (Carrier-Grade NAT)
        // 127.0.0.0/8 (Loopback)
        // 169.254.0.0/16 (Link Local / Cloud Metadata)
        // 172.16.0.0/12 (Private)
        // 192.0.0.0/24 (IETF Protocol Assignments)
        // 192.0.2.0/24 (TEST-NET-1)
        // 192.168.0.0/16 (Private)
        // 198.18.0.0/15 (Benchmarking)
        // 198.51.100.0/24 (TEST-NET-2)
        // 203.0.113.0/24 (TEST-NET-3)
        // 224.0.0.0/4 (Multicast)
        // 240.0.0.0/4 (Reserved)
        $ranges = [
            ['0.0.0.0', '255.0.0.0'],
            ['10.0.0.0', '255.0.0.0'],
            ['100.64.0.0', '255.192.0.0'],
            ['127.0.0.0', '255.0.0.0'],
            ['169.254.0.0', '255.255.0.0'],
            ['172.16.0.0', '255.240.0.0'],
            ['192.0.0.0', '255.255.255.0'],
            ['192.0.2.0', '255.255.255.0'],
            ['192.168.0.0', '255.255.0.0'],
            ['198.18.0.0', '255.254.0.0'],
            ['198.51.100.0', '255.255.255.0'],
            ['203.0.113.0', '255.255.255.0'],
            ['224.0.0.0', '240.0.0.0'],
            ['240.0.0.0', '240.0.0.0'],
        ];

        foreach ($ranges as [$subnet, $mask]) {
            if (($long & ip2long($mask)) === (ip2long($subnet) & ip2long($mask))) {
                return true;
            }
        }

        return false;
    }

    protected function isRestrictedIpv6(string $ip): bool
    {
        $bin = inet_pton($ip);
        if ($bin === false) {
            return true;
        }

        // ::1 loopback
        if ($ip === '::1' || $ip === '0:0:0:0:0:0:0:1') {
            return true;
        }

        // :: unspecified
        if ($ip === '::') {
            return true;
        }

        // fe80::/10 link-local
        // fc00::/7 unique local
        // ff00::/8 multicast
        $firstByte = ord($bin[0]);
        if ($firstByte === 0xFE && (ord($bin[1]) & 0xC0) === 0x80) {
            return true; // fe80::/10
        }
        if (($firstByte & 0xFE) === 0xFC) {
            return true; // fc00::/7
        }
        if ($firstByte === 0xFF) {
            return true; // ff00::/8
        }

        // IPv4-mapped IPv6 (::ffff:0:0/96)
        if (substr($bin, 0, 12) === str_repeat("\x00", 10) . "\xFF\xFF") {
            $ipv4 = inet_ntop(substr($bin, 12, 4));
            return $this->isRestrictedIpv4($ipv4);
        }

        return false;
    }

    /**
     * Safely fetch a remote URL with strict SSRF controls, size limits, and revalidated redirects.
     */
    public function safeFetch(string $url, ?int $timeout = null): array
    {
        $timeout = $timeout ?? (int) config('linkpilot.limits.request_timeout', self::DEFAULT_TIMEOUT);
        $maxSize = (int) config('linkpilot.limits.max_response_size', self::MAX_RESPONSE_SIZE);

        $currentUrl = $this->validateUrl($url);
        $redirectHistory = [];
        $startTime = microtime(true);

        $ch = curl_init();
        try {
            for ($i = 0; $i <= self::MAX_REDIRECTS; $i++) {
                // Revalidate every URL in the redirect chain before connection
                $this->validateUrl($currentUrl);

                $redirectHistory[] = $currentUrl;

                curl_setopt_array($ch, [
                    CURLOPT_URL => $currentUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => false, // We handle redirects manually for security
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
                    CURLOPT_MAXFILESIZE => $maxSize,
                    CURLOPT_USERAGENT => 'LinkPilot-SEO-Bot/1.0 (+https://linkpilot.internal/bot)',
                    CURLOPT_HEADER => true,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ]);

                $response = curl_exec($ch);
                $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    return [
                        'success' => false,
                        'http_status' => null,
                        'final_url' => $currentUrl,
                        'redirect_history' => $redirectHistory,
                        'redirect_count' => count($redirectHistory) - 1,
                        'latency_ms' => $latencyMs,
                        'headers' => [],
                        'body' => null,
                        'content_type' => null,
                        'error' => "cURL error: {$error}",
                    ];
                }

                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $headerText = substr($response, 0, $headerSize);
                $body = substr($response, $headerSize);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

                // Check for redirect status codes 301, 302, 303, 307, 308
                if (in_array($httpCode, [301, 302, 303, 307, 308], true)) {
                    $location = null;
                    if (preg_match('/^Location:\s*(.*?)$/mi', $headerText, $matches)) {
                        $location = trim($matches[1]);
                    }

                    if (!$location) {
                        break;
                    }

                    // Resolve relative redirect location against current URL
                    $currentUrl = $this->resolveRelativeUrl($currentUrl, $location);
                    continue;
                }

                // Non-redirect response reached
                $parsedHeaders = $this->parseHeaders($headerText);

                return [
                    'success' => true,
                    'http_status' => $httpCode,
                    'final_url' => $currentUrl,
                    'redirect_history' => $redirectHistory,
                    'redirect_count' => count($redirectHistory) - 1,
                    'latency_ms' => $latencyMs,
                    'headers' => $parsedHeaders,
                    'body' => $body,
                    'content_type' => $contentType,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'http_status' => 310,
                'final_url' => $currentUrl,
                'redirect_history' => $redirectHistory,
                'redirect_count' => count($redirectHistory) - 1,
                'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'headers' => [],
                'body' => null,
                'content_type' => null,
                'error' => 'Too many redirects (exceeded limit of ' . self::MAX_REDIRECTS . ').',
            ];
        } finally {
            curl_close($ch);
        }
    }

    protected function resolveRelativeUrl(string $baseUrl, string $relUrl): string
    {
        if (parse_url($relUrl, PHP_URL_SCHEME) != '') {
            return $relUrl;
        }

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';
        $port = isset($base['port']) ? ':' . $base['port'] : '';

        if (str_starts_with($relUrl, '//')) {
            return $scheme . ':' . $relUrl;
        }

        if (str_starts_with($relUrl, '/')) {
            return "{$scheme}://{$host}{$port}{$relUrl}";
        }

        $basePath = $base['path'] ?? '/';
        $dir = dirname($basePath);
        if ($dir === '\\' || $dir === '.') {
            $dir = '/';
        }
        $dir = rtrim($dir, '/') . '/';

        return "{$scheme}://{$host}{$port}{$dir}{$relUrl}";
    }

    protected function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        $lines = explode("\r\n", $rawHeaders);
        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$key, $val] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($val);
            }
        }
        return $headers;
    }
}

<?php

namespace App\Services\Analysis;

use App\Models\Backlink;
use App\Models\HealthCheck;
use App\Services\Security\SsrfProtectionService;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Log;

class UrlHealthAnalyzerService
{
    public function __construct(
        protected SsrfProtectionService $ssrfService
    ) {}

    /**
     * Perform complete health check and backlink verification on a backlink record.
     */
    public function analyze(Backlink $backlink): array
    {
        $sourceUrl = $backlink->source_url;
        $targetUrl = $backlink->target_url;

        // 1. Fetch remote source page with SSRF protection
        $fetchResult = $this->ssrfService->safeFetch($sourceUrl);

        $httpStatus = $fetchResult['http_status'];
        $latencyMs = $fetchResult['latency_ms'];
        $finalUrl = $fetchResult['final_url'];
        $redirectCount = $fetchResult['redirect_count'];
        $headers = $fetchResult['headers'] ?? [];
        $body = $fetchResult['body'] ?? '';
        $contentType = $fetchResult['content_type'];
        $isHttps = str_starts_with(strtolower($finalUrl), 'https://');

        $xRobotsTag = $headers['x-robots-tag'] ?? null;
        $metaRobots = null;
        $canonicalUrl = null;
        $pageTitle = null;
        $backlinkFound = false;
        $targetUrlFound = null;
        $anchorTextFound = null;
        $relAttributesFound = [];
        $linkType = 'unknown';
        $robotsTxtStatus = 'allowed'; // default check
        $errorMessage = $fetchResult['error'];

        if ($fetchResult['success'] && !empty($body)) {
            $parsed = $this->parseHtml($body, $targetUrl);
            $metaRobots = $parsed['meta_robots'];
            $canonicalUrl = $parsed['canonical_url'];
            $pageTitle = $parsed['page_title'];
            $backlinkFound = $parsed['backlink_found'];
            $targetUrlFound = $parsed['target_url_found'];
            $anchorTextFound = $parsed['anchor_text_found'];
            $relAttributesFound = $parsed['rel_attributes'];
            $linkType = $parsed['link_type'];
        }

        // Determine robots indexing permission status
        $robotsCombined = trim(($metaRobots ?? '') . ' ' . ($xRobotsTag ?? ''));
        $isNoindex = preg_match('/\bnoindex\b/i', $robotsCombined) === 1;

        $indexability = 'unknown';
        if ($httpStatus === 200) {
            $indexability = $isNoindex ? 'not_indexable' : 'indexable';
        } elseif (in_array($httpStatus, [301, 302, 307, 308], true)) {
            $indexability = 'redirect';
        } elseif ($httpStatus >= 400 || !$fetchResult['success']) {
            $indexability = 'broken';
        }

        $passed = ($httpStatus === 200 && $backlinkFound && !$isNoindex);

        // Record health check
        $healthCheck = HealthCheck::create([
            'backlink_id' => $backlink->id,
            'source_url' => $sourceUrl,
            'http_status' => $httpStatus,
            'response_time_ms' => $latencyMs,
            'redirect_count' => $redirectCount,
            'final_url' => $finalUrl,
            'canonical_url' => $canonicalUrl,
            'meta_robots' => $metaRobots,
            'x_robots_tag' => $xRobotsTag,
            'robots_txt_status' => $robotsTxtStatus,
            'content_type' => $contentType,
            'is_https' => $isHttps,
            'page_title' => $pageTitle,
            'backlink_found' => $backlinkFound,
            'target_url_found' => $targetUrlFound,
            'anchor_text_found' => $anchorTextFound,
            'rel_attributes_found' => $relAttributesFound,
            'passed' => $passed,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);

        return [
            'health_check' => $healthCheck,
            'http_status' => $httpStatus,
            'final_url' => $finalUrl,
            'canonical_url' => $canonicalUrl,
            'robots_status' => $robotsCombined ?: 'index,follow',
            'indexability' => $indexability,
            'is_live' => $backlinkFound,
            'link_type' => $linkType,
            'rel_attributes' => $relAttributesFound,
            'anchor_text' => $anchorTextFound,
            'passed' => $passed,
            'error' => $errorMessage,
        ];
    }

    /**
     * Safely parse HTML body without external network access or entity expansion.
     */
    public function parseHtml(string $html, string $expectedTargetUrl): array
    {
        $metaRobots = null;
        $canonicalUrl = null;
        $pageTitle = null;
        $backlinkFound = false;
        $targetUrlFound = null;
        $anchorTextFound = null;
        $relAttributes = [];
        $linkType = 'unknown';

        if (empty($html)) {
            return compact('metaRobots', 'canonicalUrl', 'pageTitle', 'backlinkFound', 'targetUrlFound', 'anchorTextFound', 'relAttributes', 'linkType');
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);

        // Convert encoding to UTF-8 entities safely
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);

        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        // Extract Page Title
        $titleNodes = $xpath->query('//title');
        if ($titleNodes && $titleNodes->length > 0) {
            $pageTitle = trim($titleNodes->item(0)->textContent);
        }

        // Extract Meta Robots
        $robotsNodes = $xpath->query('//meta[translate(@name, "ROBOTS", "robots")="robots"]/@content');
        if ($robotsNodes && $robotsNodes->length > 0) {
            $metaRobots = trim($robotsNodes->item(0)->nodeValue);
        }

        // Extract Canonical Link
        $canonicalNodes = $xpath->query('//link[translate(@rel, "CANONICAL", "canonical")="canonical"]/@href');
        if ($canonicalNodes && $canonicalNodes->length > 0) {
            $canonicalUrl = trim($canonicalNodes->item(0)->nodeValue);
        }

        // Normalize expected target URL
        $normalizedExpected = $this->normalizeUrl($expectedTargetUrl);

        // Extract and analyze links
        $linkNodes = $xpath->query('//a[@href]');
        if ($linkNodes) {
            foreach ($linkNodes as $node) {
                $href = trim($node->getAttribute('href'));
                $normalizedHref = $this->normalizeUrl($href);

                if ($this->urlsMatch($normalizedExpected, $normalizedHref)) {
                    $backlinkFound = true;
                    $targetUrlFound = $href;
                    $anchorTextFound = trim($node->textContent);

                    $rawRel = strtolower(trim($node->getAttribute('rel')));
                    $relList = array_values(array_filter(preg_split('/\s+/', $rawRel)));
                    $relAttributes = $relList;

                    if (in_array('nofollow', $relList, true)) {
                        $linkType = 'nofollow';
                    } elseif (in_array('sponsored', $relList, true)) {
                        $linkType = 'sponsored';
                    } elseif (in_array('ugc', $relList, true)) {
                        $linkType = 'ugc';
                    } else {
                        $linkType = 'dofollow';
                    }
                    break;
                }
            }
        }

        return [
            'meta_robots' => $metaRobots,
            'canonical_url' => $canonicalUrl,
            'page_title' => $pageTitle,
            'backlink_found' => $backlinkFound,
            'target_url_found' => $targetUrlFound,
            'anchor_text_found' => $anchorTextFound,
            'rel_attributes' => $relAttributes,
            'link_type' => $linkType,
        ];
    }

    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);
        $parsed = parse_url($url);
        if (!$parsed) {
            return strtolower($url);
        }

        $host = strtolower($parsed['host'] ?? '');
        $host = preg_replace('/^www\./', '', $host);
        $path = rtrim($parsed['path'] ?? '/', '/');
        if ($path === '') {
            $path = '/';
        }

        return $host . $path;
    }

    protected function urlsMatch(string $expected, string $actual): bool
    {
        if ($expected === $actual) {
            return true;
        }

        // Match domain and trailing paths
        return str_ends_with($actual, $expected) || str_ends_with($expected, $actual);
    }
}

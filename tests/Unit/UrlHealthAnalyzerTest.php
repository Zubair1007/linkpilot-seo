<?php

namespace Tests\Unit;

use App\Services\Analysis\UrlHealthAnalyzerService;
use App\Services\Security\SsrfProtectionService;
use PHPUnit\Framework\TestCase;

class UrlHealthAnalyzerTest extends TestCase
{
    protected UrlHealthAnalyzerService $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new UrlHealthAnalyzerService(new SsrfProtectionService());
    }

    public function test_parses_html_metadata_and_dofollow_backlink(): void
    {
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Top Tech Innovations 2026</title>
            <meta name="robots" content="index, follow">
            <link rel="canonical" href="https://partner.com/tech-innovations">
        </head>
        <body>
            <p>Check out our partner <a href="https://acme.io/features">Acme Cloud Tools</a> for scale.</p>
        </body>
        </html>
        HTML;

        $result = $this->analyzer->parseHtml($html, 'https://acme.io/features');

        $this->assertEquals('Top Tech Innovations 2026', $result['page_title']);
        $this->assertEquals('index, follow', $result['meta_robots']);
        $this->assertEquals('https://partner.com/tech-innovations', $result['canonical_url']);
        $this->assertTrue($result['backlink_found']);
        $this->assertEquals('Acme Cloud Tools', $result['anchor_text_found']);
        $this->assertEquals('dofollow', $result['link_type']);
    }

    public function test_detects_nofollow_and_sponsored_attributes(): void
    {
        $html = <<<HTML
        <html>
        <body>
            <a href="https://acme.io/signup" rel="nofollow sponsored">Try Acme</a>
        </body>
        </html>
        HTML;

        $result = $this->analyzer->parseHtml($html, 'https://acme.io/signup');

        $this->assertTrue($result['backlink_found']);
        $this->assertEquals('nofollow', $result['link_type']);
        $this->assertContains('sponsored', $result['rel_attributes']);
    }

    public function test_detects_missing_backlink(): void
    {
        $html = '<html><body><p>Article with no partner link.</p></body></html>';
        $result = $this->analyzer->parseHtml($html, 'https://acme.io/product');

        $this->assertFalse($result['backlink_found']);
        $this->assertNull($result['anchor_text_found']);
    }
}

<?php

namespace Tests\Unit;

use App\Services\Security\SsrfProtectionService;
use Exception;
use PHPUnit\Framework\TestCase;

class SsrfProtectionTest extends TestCase
{
    protected SsrfProtectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SsrfProtectionService();
    }

    public function test_blocks_localhost_and_loopback(): void
    {
        $this->expectException(Exception::class);
        $this->service->validateUrl('http://localhost:8000/admin');
    }

    public function test_blocks_127_0_0_1(): void
    {
        $this->expectException(Exception::class);
        $this->service->validateUrl('http://127.0.0.1/test');
    }

    public function test_blocks_cloud_metadata_ip(): void
    {
        $this->expectException(Exception::class);
        $this->service->validateUrl('http://169.254.169.254/latest/meta-data/');
    }

    public function test_blocks_unsupported_protocols(): void
    {
        $this->expectException(Exception::class);
        $this->service->validateUrl('file:///etc/passwd');
    }

    public function test_identifies_private_ipv4_subnets(): void
    {
        $this->assertTrue($this->service->isRestrictedIp('10.0.0.5'));
        $this->assertTrue($this->service->isRestrictedIp('192.168.1.100'));
        $this->assertTrue($this->service->isRestrictedIp('172.16.0.1'));
        $this->assertTrue($this->service->isRestrictedIp('127.0.0.1'));
        $this->assertTrue($this->service->isRestrictedIp('169.254.1.1'));

        // Public IP should not be restricted
        $this->assertFalse($this->service->isRestrictedIp('8.8.8.8'));
        $this->assertFalse($this->service->isRestrictedIp('1.1.1.1'));
    }
}

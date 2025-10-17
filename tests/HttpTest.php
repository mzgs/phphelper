<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpHelper\Http;

final class HttpTest extends TestCase
{
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER ?? [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        parent::tearDown();
    }

    public function testClientInfoParsesServerGlobals(): void
    {
        $_SERVER = [
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.9, 10.0.0.1',
            'REMOTE_ADDR' => '198.51.100.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8,fr;q=0.6',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_REFERER' => 'https://ref.example',
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.com',
            'SERVER_PORT' => '443',
            'REQUEST_URI' => '/path/page?x=1&y=2',
        ];

        $info = Http::clientInfo();

        $this->assertSame('https', $info['scheme']);
        $this->assertSame('example.com', $info['host']);
        $this->assertSame(443, $info['port']);
        $this->assertSame('/path/page', $info['path']);
        $this->assertSame('x=1&y=2', $info['query']);
        $this->assertSame('https://example.com/path/page?x=1&y=2', $info['url']);

        $this->assertSame('203.0.113.9', $info['ip']);
        $this->assertTrue($info['is_proxy']);
        $this->assertSame(['203.0.113.9', '10.0.0.1', '198.51.100.1'], $info['ips']);

        $this->assertSame('Chrome', $info['browser']);
        $this->assertSame('120.0.0.0', $info['browser_version']);
        $this->assertSame('Windows', $info['os']);
        $this->assertSame('Blink', $info['engine']);

        $this->assertSame('desktop', $info['device']);
        $this->assertFalse($info['is_mobile']);
        $this->assertFalse($info['is_tablet']);
        $this->assertTrue($info['is_desktop']);
        $this->assertFalse($info['is_bot']);

        $this->assertSame('en-US,en;q=0.8,fr;q=0.6', $info['accept_language']);
        $this->assertSame(['en-US', 'en', 'fr'], $info['languages']);
        $this->assertSame('GET', $info['method']);
        $this->assertSame('https://ref.example', $info['referer']);
    }
}


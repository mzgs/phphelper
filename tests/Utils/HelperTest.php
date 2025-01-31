<?php

namespace Tests\Utils;

use PHPUnit\Framework\TestCase;
use PhpHelper\Utils\Helper;

class HelperTest extends TestCase
{
    public function testSlugify(): void
    {
        $this->assertEquals('hello-world', Helper::slugify('Hello World'));
        $this->assertEquals('hello-world', Helper::slugify('Hello, World!'));
        $this->assertEquals('hello_world', Helper::slugify('Hello World', '_'));
        $this->assertEquals('hello-world-123', Helper::slugify('Hello World 123'));
    }

    public function testFormatBytes(): void
    {
        $this->assertEquals('1 KB', Helper::formatBytes(1024));
        $this->assertEquals('1 MB', Helper::formatBytes(1024 * 1024));
        $this->assertEquals('1 GB', Helper::formatBytes(1024 * 1024 * 1024));
        $this->assertEquals('1.5 KB', Helper::formatBytes(1536));
    }

    public function testFormatDuration(): void
    {
        $this->assertEquals('30 sec', Helper::formatDuration(30));
        $this->assertEquals('2m 30s', Helper::formatDuration(150));
        $this->assertEquals('1h 30m', Helper::formatDuration(5400));
        $this->assertEquals('1d 1h', Helper::formatDuration(90000));
    }

    public function testTruncate(): void
    {
        $this->assertEquals('He...', Helper::truncate('Hello World', 5));
        $this->assertEquals('Hello World', Helper::truncate('Hello World', 11));
        $this->assertEquals('He**', Helper::truncate('Hello World', 4, '**'));
    }

    public function testMask(): void
    {
        $this->assertEquals('1234****', Helper::mask('12345678', 4));
        $this->assertEquals('12**5678', Helper::mask('12345678', 2, 2));
        $this->assertEquals('1234###8', Helper::mask('12345678', 4, 3, '#'));
    }

    public function testGenerateRandomString(): void
    {
        $random = Helper::generateRandomString();
        $this->assertEquals(16, strlen($random));

        $random = Helper::generateRandomString(8);
        $this->assertEquals(8, strlen($random));

        $random = Helper::generateRandomString(5, '123');
        $this->assertEquals(5, strlen($random));
        $this->assertEquals(1, preg_match('/^[123]+$/', $random));
    }

    public function testIsValidEmail(): void
    {
        $this->assertTrue(Helper::isValidEmail('test@example.com'));
        $this->assertTrue(Helper::isValidEmail('test.name@sub.example.com'));
        $this->assertFalse(Helper::isValidEmail('invalid-email'));
        $this->assertFalse(Helper::isValidEmail('test@'));
    }

    public function testIsValidUrl(): void
    {
        $this->assertTrue(Helper::isValidUrl('https://example.com'));
        $this->assertTrue(Helper::isValidUrl('http://sub.example.com/path'));
        $this->assertFalse(Helper::isValidUrl('invalid-url'));
        $this->assertFalse(Helper::isValidUrl('http://'));
    }

    public function testIsValidIp(): void
    {
        $this->assertTrue(Helper::isValidIp('192.168.1.1'));
        $this->assertTrue(Helper::isValidIp('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
        $this->assertFalse(Helper::isValidIp('256.256.256.256'));
        $this->assertFalse(Helper::isValidIp('invalid-ip'));
    }

    public function testSanitizeString(): void
    {
        $this->assertEquals('Hello World', Helper::sanitizeString('Hello World'));
        $this->assertEquals('&lt;p&gt;Hello World&lt;/p&gt;', Helper::sanitizeString('<p>Hello World</p>'));
        $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;', Helper::sanitizeString('<script>alert(1)</script>'));
    }

    public function testExtractUrls(): void
    {
        $text = 'Visit https://example.com and http://test.com or www.example.org';
        $urls = Helper::extractUrls($text);

        $this->assertCount(3, $urls);
        $this->assertContains('https://example.com', $urls);
        $this->assertContains('http://test.com', $urls);
        $this->assertContains('www.example.org', $urls);
    }

    public function testGenerateUuid(): void
    {
        $uuid = Helper::generateUuid();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid);
    }
}
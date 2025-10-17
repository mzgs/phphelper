<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpHelper\Format;

final class FormatTest extends TestCase
{
    public function testBytesFormatsBinaryAndSi(): void
    {
        $this->assertSame('0 B', Format::bytes(0));
        $this->assertSame('2 KB', Format::bytes(2048));
        $this->assertSame('1.5 KB', Format::bytes(1536));
        $this->assertSame('1.5 KiB', Format::bytes(1536, 1, 'iec'));
        $this->assertSame('1.5 MB', Format::bytes(1_500_000, 1, 'si'));
    }

    public function testParseBytesUnderstandsCommonUnits(): void
    {
        $this->assertSame(2048, Format::parseBytes('2 KB'));
        $this->assertSame(1536, Format::parseBytes('1.5 KB'));
        $this->assertSame(2097152, Format::parseBytes('2 MiB'));
        $mb = Format::parseBytes('1.5 MB');
        $this->assertContains($mb, [1_572_864, 1_500_000]);
        $mOnly = Format::parseBytes('1.5M');
        $this->assertContains($mOnly, [1_572_864, 1_500_000]);
        $this->assertSame(500, Format::parseBytes('500 bytes'));
        $this->assertSame(0, Format::parseBytes(''));
        $this->assertSame(0, Format::parseBytes('not a size'));
    }

    public function testShortNumberAndDurationAndHms(): void
    {
        $this->assertSame('950', Format::shortNumber(950));
        $this->assertSame('1.2K', Format::shortNumber(1200));
        $this->assertSame('-1.5K', Format::shortNumber(-1500));
        $this->assertSame('1.3M', Format::shortNumber(1_250_000));

        $this->assertSame('1h 1m 1s', Format::duration(3661, true));
        $this->assertSame('1 hour, 1 minute, 1 second', Format::duration(3661, false));

        $this->assertSame('01:01:01', Format::hms(3661));
        $this->assertSame('1d 01:01:01', Format::hms(90061, true));
    }

    public function testOrdinal(): void
    {
        $this->assertSame('1st', Format::ordinal(1));
        $this->assertSame('2nd', Format::ordinal(2));
        $this->assertSame('3rd', Format::ordinal(3));
        $this->assertSame('4th', Format::ordinal(4));
        $this->assertSame('11th', Format::ordinal(11));
        $this->assertSame('13th', Format::ordinal(13));
        $this->assertSame('22nd', Format::ordinal(22));
    }

    public function testBoolAndJsonAndNumber(): void
    {
        $this->assertSame('-', Format::bool(null, 'Y', 'N', '-'));
        $this->assertSame('Y', Format::bool(true, 'Y', 'N', '-'));
        $this->assertSame('N', Format::bool(0, 'Y', 'N', '-'));
        $this->assertSame('Y', Format::bool('on', 'Y', 'N', '-'));
        $this->assertSame('N', Format::bool('No', 'Y', 'N', '-'));
        $this->assertSame('Y', Format::bool('hello', 'Y', 'N', '-'));

        $expectedPretty = json_encode(['a' => 1], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $this->assertSame($expectedPretty, Format::json(['a' => 1]));

        $res = tmpfile();
        try {
            $this->assertSame('null', Format::json($res));
        } finally {
            fclose($res);
        }

        $this->assertSame('1,234.57', Format::number(1234.567, 2, '.', ','));
    }

    public function testCurrencyIntlOrFallback(): void
    {
        if (class_exists(\NumberFormatter::class)) {
            $out = Format::currency(1234.56, 'USD', 'en_US', 2);
            // Allow either symbol or code as some environments format differently
            $this->assertMatchesRegularExpression('/(\$|USD).*1,234\.56|1,234\.56.*(\$|USD)/', $out);
        } else {
            $out = Format::currency(1234.56, 'usd', null, 2);
            $this->assertSame('1,234.56 USD', $out);
        }
    }
}

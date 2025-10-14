<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Date.php';

final class DateTest extends TestCase
{
    public function testAgoProducesRelativeStrings(): void
    {
        $this->assertSame('just now', Date::ago(time()));
        $this->assertSame('2 seconds ago', Date::ago(time() - 2));
        $future = Date::ago(time() + 3600);
        $this->assertMatchesRegularExpression('/^(59 minutes|1 hour) from now$/', $future);
    }

    public function testFormatPresetsAndTimezone(): void
    {
        $this->assertSame('2025-01-02', Date::format('2025-01-02 15:04:05', 'date', 'UTC'));
        $this->assertSame('2025-01-02T15:04:05+00:00', Date::format('2025-01-02 15:04:05', 'iso', 'UTC'));
        $this->assertSame('2 Jan 2025', Date::format('2025-01-02 15:04:05', 'human', 'UTC'));
        $this->assertSame('02/01/2025 15:04', Date::format('2025-01-02 15:04:05', 'd/m/Y H:i', 'UTC'));
    }

    public function testTimestampFromVariousInputs(): void
    {
        $this->assertSame(1, Date::timestamp('1970-01-01 00:00:01', 'UTC'));

        $dt = new DateTimeImmutable('1970-01-01 00:00:01', new DateTimeZone('UTC'));
        $this->assertSame(1, Date::timestamp($dt));

        $this->assertSame(123, Date::timestamp(123));
    }
}

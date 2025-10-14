<?php

// Example usage of helpers in this repository.

require_once __DIR__ . '/src/DB.php';
require_once __DIR__ . '/src/Str.php';
require_once __DIR__ . '/src/Format.php';
require_once __DIR__ . '/src/Http.php';

// Small helpers for tidy output
function longLine(int $length = 64, string $char = '-'): void
{
    echo str_repeat($char, max(1, $length)) . PHP_EOL;
}

function section(string $title): void
{
    static $sectionCount = 0;
    if ($sectionCount > 0) {
        longLine(); // separator between sections
    }
    echo PHP_EOL . '-- ' . $title . ' --' . PHP_EOL;
    $sectionCount++;
}

function line(string $label, mixed $value): void
{
    echo $label . $value . PHP_EOL;
}

section('Database examples');



DB::mysql('test_db', 'root', '1'); 
// line('Rows: ', print_r(DB::getRows('SELECT * FROM users'), true));
line('DB users count (age >= 30): ', DB::count('users', 'age >= ?', [30]));

// String helpers
section('String examples');
line('Str::upper(tr): ', Str::upper('Başlık türkçe isim çöğş', 'tr'));
line('Str::lower(tr): ', Str::lower('BAŞLIK TÜRKÇE İSİM ÇÖĞŞ', 'tr'));
line('Str::title(tr): ', Str::title('BAŞLIK TÜRKÇE İSİM ÇÖĞŞ', 'tr'));
line('Str::seoUrl: ', Str::seoUrl('Dosya İÇerik.png'));

// Format helpers
section('Format examples');

// Bytes
line('bytes(1536): ', Format::bytes(1536));                                 // 1.50 KB (binary)
              // 1.50 KiB (IEC)
// Number
line('number(1234567.891, 2): ', Format::number(1234567.891, 2));            // 1,234,567.89

// Currency (uses intl when available)
line('currency(1234.56, USD): ', Format::currency(1234.56, 'USD'));
line('currency(1234.56, TRY): ', Format::currency(1234.56, 'TRY', 'tr_TR',0));

// Percent
line('percent(0.1234, 2): ', Format::percent(0.1234, 2));                    // 12.34%
line('percent(12.34, 1, false): ', Format::percent(12.34, 1, false));        // 12.3%

// Short number
line('shortNumber(1530): ', Format::shortNumber(1530));                       // 1.5K

// Durations
line('duration(3723): ', Format::duration(3723));                             // 1h 2m 3s
line('duration(3723, false): ', Format::duration(3723, false));               // 1 hour, 2 minutes, 3 seconds
line('hms(3723): ', Format::hms(3723));                                       // 01:02:03
line('hms(90061, true): ', Format::hms(90061, true));                         // 1d 01:01:01

// Ordinal
line('ordinal(21): ', Format::ordinal(21));                                   // 21st

// Parse bytes
line('parseBytes("1.5 GB"): ', Format::parseBytes('1.5 GB'));               // 1500000000 (SI)
line('parseBytes("2MiB"): ', Format::parseBytes('2MiB'));                   // 2097152 (IEC)

// Boolean labels
line('bool(true): ', Format::bool(true));                                     // Yes
line('bool("no"): ', Format::bool('no'));                                    // No

echo "<br>";
echo "<br>";
echo "<br>";

echo "<pre>";
print_r(Http::clientInfo());
echo "</pre>";
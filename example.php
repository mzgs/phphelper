<?php

require_once __DIR__ . '/src/DB.php';
require_once __DIR__ . '/src/Str.php';
require_once __DIR__ . '/src/Format.php';


DB::mysql("test_db", "root", "1");
// print_r(DB::getRows("SELECT * FROM users"));
// echo DB::getValue("SELECT username FROM users LIMIT 1") . PHP_EOL;
echo DB::count("users", "age >= ?", [30]) . PHP_EOL;

echo Str::upper("Başlık türkçe isim çöğş","tr") . PHP_EOL;
echo Str::lower("BAŞLIK TÜRKÇE İSİM ÇÖĞŞ","tr") . PHP_EOL;
echo Str::title("BAŞLIK TÜRKÇE İSİM ÇÖĞŞ","tr") . PHP_EOL;
echo Str::seoUrl('Dosya İÇerik.png') . PHP_EOL;

// Format examples
echo "\n-- Format examples --\n";

// Bytes
echo 'bytes(1536): ' . Format::bytes(1536) . PHP_EOL;                        // 1.50 KB (binary)
echo 'bytes(1536, 2, si): ' . Format::bytes(1536, 2, 'si') . PHP_EOL;        // 1.54 KB (SI)
echo 'bytes(1536, 2, iec): ' . Format::bytes(1536, 2, 'iec') . PHP_EOL;      // 1.50 KiB (IEC)

// Number
echo 'number(1234567.891, 2): ' . Format::number(1234567.891, 2) . PHP_EOL;  // 1,234,567.89

// Currency (uses intl when available)
echo 'currency(1234.56, USD): ' . Format::currency(1234.56, 'USD') . PHP_EOL;
echo 'currency(1234.56, USD, null, 0): ' . Format::currency(1234.56, 'USD', null, 0) . PHP_EOL; // override precision
echo 'currency(1234.56, TRY): ' . Format::currency(1234.56, 'TRY', 'tr_TR') . PHP_EOL;

// Percent
echo 'percent(0.1234, 2): ' . Format::percent(0.1234, 2) . PHP_EOL;          // 12.34%
echo 'percent(12.34, 1, false): ' . Format::percent(12.34, 1, false) . PHP_EOL; // 12.3%

// Short number
echo 'shortNumber(1530): ' . Format::shortNumber(1530) . PHP_EOL;            // 1.5K

// Durations
echo 'duration(3723): ' . Format::duration(3723) . PHP_EOL;                  // 1h 2m 3s
echo 'duration(3723, false): ' . Format::duration(3723, false) . PHP_EOL;    // 1 hour, 2 minutes, 3 seconds
echo 'hms(3723): ' . Format::hms(3723) . PHP_EOL;                            // 01:02:03
echo 'hms(90061, true): ' . Format::hms(90061, true) . PHP_EOL;              // 1d 01:01:01

// Ordinal
echo 'ordinal(21): ' . Format::ordinal(21) . PHP_EOL;                         // 21st

// Parse bytes
echo 'parseBytes("1.5 GB"): ' . Format::parseBytes('1.5 GB') . PHP_EOL;     // 1500000000 (SI)
echo 'parseBytes("2MiB"): ' . Format::parseBytes('2MiB') . PHP_EOL;         // 2097152 (IEC)

// Boolean labels
echo 'bool(true): ' . Format::bool(true) . PHP_EOL;                           // Yes
echo 'bool("no"): ' . Format::bool('no') . PHP_EOL;                          // No

// JSON display
echo 'json: ' . PHP_EOL . Format::json(['a' => 1, 'b' => [2, 3]]) . PHP_EOL;

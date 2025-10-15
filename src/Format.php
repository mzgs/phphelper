<?php

class Format
{
    /**
     * Format bytes into a human readable string.
     *
     * Examples: 0 -> "0 B", 1536 -> "1.50 KB"
     *
     * @param int $bytes          Size in bytes
     * @param int $precision      Decimal precision
     * @param string $system      One of: 'binary' (1024, KB/MB), 'iec' (1024, KiB/MiB), 'si' (1000, KB/MB)
     * @param array<string> $units Optional custom unit labels overriding the chosen system
     */
    public static function bytes(int $bytes, int $precision = 2, string $system = 'binary', ?array $units = null): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $system = strtolower($system);
        $base = 1024;
        $defaultUnits = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        if ($system === 'iec') {
            $base = 1024;
            $defaultUnits = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
        } elseif ($system === 'si') {
            $base = 1000;
            $defaultUnits = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        }

        $units = is_array($units) && !empty($units) ? array_values($units) : $defaultUnits;

        $pow = (int) floor(log($bytes, $base));
        $pow = max(0, min($pow, count($units) - 1));
        $value = $bytes / ($base ** $pow);

        return round($value, $precision) . ' ' . $units[$pow];
    }

    /**
     * Standard number formatting wrapper.
     */
    public static function number(float|int $value, int $decimals = 0, string $decimalPoint = '.', string $thousandsSep = ','): string
    {
        return number_format((float) $value, $decimals, $decimalPoint, $thousandsSep);
    }

    /**
     * Format a currency amount using intl NumberFormatter when available.
     * Falls back to a simple "123,456.78 USD" formatting.
     *
     * @param float $amount
     * @param string $currency ISO 4217 code (e.g., USD, EUR)
     * @param string|null $locale e.g., en_US; when null uses default locale
     * @param int|null $precision Override fraction digits; null keeps formatter/currency defaults
     */
    public static function currency(float $amount, string $currency = 'USD', ?string $locale = null, ?int $precision = null): string
    {
        if (class_exists(\NumberFormatter::class)) {
            $loc = $locale;
            if ($loc === null || $loc === '') {
                if (function_exists('locale_get_default')) {
                    $loc = locale_get_default();
                } else {
                    $loc = 'en_US';
                }
            }

            try {
                $fmt = new \NumberFormatter($loc, \NumberFormatter::CURRENCY);
                if ($precision !== null) {
                    $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, max(0, $precision));
                }
                $str = $fmt->formatCurrency($amount, $currency);
                if (is_string($str)) {
                    return $str;
                }
            } catch (\Throwable) {
                // ignore, fall through to fallback
            }
        }

        // Fallback
        $decimals = $precision ?? 2;
        $formatted = number_format($amount, $decimals, '.', ',');
        return $formatted . ' ' . strtoupper($currency);
    }

    /**
     * Format percentage. If $fromFraction is true, treats input as a fraction (0.12 -> 12%).
     */
    public static function percent(float $value, int $precision = 0, bool $fromFraction = true): string
    {
        $v = $fromFraction ? ($value * 100) : $value;
        return number_format($v, $precision, '.', ',') . '%';
    }

    /**
     * Humanized abbreviations: 1.2K, 3.4M, 5.6B, 7.8T.
     */
    public static function shortNumber(float|int $value, int $precision = 1): string
    {
        $num = (float) $value;
        $sign = $num < 0 ? '-' : '';
        $abs = abs($num);

        $suffix = '';
        $divisor = 1.0;
        if ($abs >= 1_000_000_000_000) { // Trillions
            $suffix = 'T';
            $divisor = 1_000_000_000_000;
        } elseif ($abs >= 1_000_000_000) { // Billions
            $suffix = 'B';
            $divisor = 1_000_000_000;
        } elseif ($abs >= 1_000_000) { // Millions
            $suffix = 'M';
            $divisor = 1_000_000;
        } elseif ($abs >= 1_000) { // Thousands
            $suffix = 'K';
            $divisor = 1_000;
        }

        if ($divisor === 1.0) {
            return $sign . number_format($abs, 0, '.', ',');
        }

        $scaled = $abs / $divisor;
        $str = number_format($scaled, $precision, '.', '');
        // Trim trailing zeros and possible trailing dot
        $str = rtrim(rtrim($str, '0'), '.');
        return $sign . $str . $suffix;
    }

    /**
     * Human time spans: "1h 2m 3s" (compact) or "1 hour, 2 minutes, 3 seconds" (verbose).
     */
    public static function duration(int $seconds, bool $compact = true): string
    {
        $seconds = max(0, $seconds);
        $d = intdiv($seconds, 86400);
        $h = intdiv($seconds % 86400, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        if ($compact) {
            $parts = [];
            if ($d > 0) { $parts[] = $d . 'd'; }
            if ($h > 0) { $parts[] = $h . 'h'; }
            if ($m > 0) { $parts[] = $m . 'm'; }
            if ($s > 0 || empty($parts)) { $parts[] = $s . 's'; }
            return implode(' ', $parts);
        }

        $parts = [];
        if ($d > 0) { $parts[] = $d . ' ' . ($d === 1 ? 'day' : 'days'); }
        if ($h > 0) { $parts[] = $h . ' ' . ($h === 1 ? 'hour' : 'hours'); }
        if ($m > 0) { $parts[] = $m . ' ' . ($m === 1 ? 'minute' : 'minutes'); }
        if ($s > 0 || empty($parts)) { $parts[] = $s . ' ' . ($s === 1 ? 'second' : 'seconds'); }
        return implode(', ', $parts);
    }

    /**
     * Clock format HH:MM:SS, optionally with days prefix as "Xd HH:MM:SS".
     */
    public static function hms(int $seconds, bool $withDays = false): string
    {
        $seconds = max(0, $seconds);
        $d = intdiv($seconds, 86400);
        $h = intdiv($seconds % 86400, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        $clock = str_pad((string)$h, 2, '0', STR_PAD_LEFT)
            . ':' . str_pad((string)$m, 2, '0', STR_PAD_LEFT)
            . ':' . str_pad((string)$s, 2, '0', STR_PAD_LEFT);

        if ($withDays && $d > 0) {
            return $d . 'd ' . $clock;
        }
        return $clock;
    }

    /**
     * English ordinal suffix: 1st, 2nd, 3rd, 4th, ...
     */
    public static function ordinal(int $number): string
    {
        $n = abs($number);
        $suffix = 'th';
        if ($n % 100 < 11 || $n % 100 > 13) {
            switch ($n % 10) {
                case 1: $suffix = 'st'; break;
                case 2: $suffix = 'nd'; break;
                case 3: $suffix = 'rd'; break;
            }
        }
        return $number . $suffix;
    }

    /**
     * Parse a human-readable size (e.g., "2M", "1.5 GB", "2MiB") into bytes.
     * Returns 0 on invalid input.
     */
    public static function parseBytes(string $size): int
    {
        $str = trim($size);
        if ($str === '') {
            return 0;
        }

        // Normalize decimal separator: if only comma exists, treat it as decimal point
        if (strpos($str, ',') !== false && strpos($str, '.') === false) {
            $str = str_replace(',', '.', $str);
        }

        if (!preg_match('/^\s*([0-9]+(?:\.[0-9]+)?)\s*([a-zA-Z]+)?\s*$/', $str, $m)) {
            return 0;
        }

        $num = (float) $m[1];
        $unit = isset($m[2]) ? strtolower($m[2]) : '';
        $unit = str_replace(['bytes', 'byte'], 'b', $unit);

        $map1024 = [
            'b' => 1,
            'k' => 1024, 'kb' => 1024, 'ki' => 1024, 'kib' => 1024,
            'm' => 1024 ** 2, 'mb' => 1024 ** 2, 'mi' => 1024 ** 2, 'mib' => 1024 ** 2,
            'g' => 1024 ** 3, 'gb' => 1024 ** 3, 'gi' => 1024 ** 3, 'gib' => 1024 ** 3,
            't' => 1024 ** 4, 'tb' => 1024 ** 4, 'ti' => 1024 ** 4, 'tib' => 1024 ** 4,
            'p' => 1024 ** 5, 'pb' => 1024 ** 5, 'pi' => 1024 ** 5, 'pib' => 1024 ** 5,
        ];

        $map1000 = [
            'kb10' => 1000, 'mb10' => 1_000_000, 'gb10' => 1_000_000_000, 'tb10' => 1_000_000_000_000, 'pb10' => 1_000_000_000_000_000,
        ];

        if ($unit === '' || $unit === 'b') {
            $bytes = (int) round($num);
        } elseif (isset($map1024[$unit])) {
            $bytes = (int) round($num * $map1024[$unit]);
        } else {
            // Try SI (KB/MB/GB/TB/PB) as 1000-based when not an IEC unit
            $u = substr($unit, 0, 1);
            if (in_array($u, ['k', 'm', 'g', 't', 'p'], true)) {
                $key = $u . 'b10';
                $bytes = (int) round($num * ($map1000[$key] ?? 0));
            } else {
                return 0;
            }
        }

        return max(0, $bytes);
    }

    /**
     * Consistent boolean labels for mixed input.
     */
    public static function bool(mixed $value, string $true = 'Yes', string $false = 'No', string $null = ''): string
    {
        if ($value === null) {
            return $null;
        }

        if (is_bool($value)) {
            return $value ? $true : $false;
        }

        if (is_numeric($value)) {
            return ((float) $value) != 0.0 ? $true : $false;
        }

        if (is_string($value)) {
            $v = strtolower(trim($value));
            if ($v === '') {
                return $false;
            }
            $truthy = ['1', 'true', 'yes', 'y', 'on', 't'];
            $falsy  = ['0', 'false', 'no', 'n', 'off', 'f'];
            if (in_array($v, $truthy, true)) {
                return $true;
            }
            if (in_array($v, $falsy, true)) {
                return $false;
            }
            // Non-empty non-boolean strings considered truthy
            return $true;
        }

        // Arrays/objects: truthy if non-empty/non-null
        return $true;
    }

    /**
     * JSON encode value for display (pretty or compact).
     */
    public static function json(mixed $value, bool $pretty = true, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string
    {
        $opts = $flags | ($pretty ? JSON_PRETTY_PRINT : 0);
        $json = json_encode($value, $opts);
        if ($json === false) {
            return 'null';
        }

        // Unescape quotes and slashes for display readability.
        $json = str_replace(["\\\"", "\\/", "\/"], ["\"", "/", "/"], $json);

        return $json;
    }
}

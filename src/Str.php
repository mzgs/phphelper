<?php
class Str
{
    /**
     * Determine if the given text starts with any of the given prefixes.
     */
    public static function startsWith(string $text, string|array $prefixes, bool $caseSensitive = true): bool
    {
        foreach ((array) $prefixes as $prefix) {
            if ($prefix === '') {
                continue;
            }
            if ($caseSensitive) {
                if (str_starts_with($text, $prefix)) {
                    return true;
                }
            } else {
                $textPrefix = substr($text, 0, strlen($prefix));
                if (strcasecmp($textPrefix, $prefix) === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine if the given text ends with any of the given suffixes.
     */
    public static function endsWith(string $text, string|array $suffixes, bool $caseSensitive = true): bool
    {
        foreach ((array) $suffixes as $suffix) {
            if ($suffix === '') {
                continue;
            }
            $len = strlen($suffix);
            if ($len === 0) {
                continue;
            }
            $textSuffix = substr($text, -$len);
            if ($caseSensitive) {
                if (str_ends_with($text, $suffix)) {
                    return true;
                }
            } else {
                if (strcasecmp($textSuffix, $suffix) === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine if the given text contains any of the given fragments.
     */
    public static function contains(string $text, string|array $fragments, bool $caseSensitive = true): bool
    {
        foreach ((array) $fragments as $fragment) {
            if ($fragment === '') {
                continue;
            }
            if ($caseSensitive) {
                if (str_contains($text, $fragment)) {
                    return true;
                }
            } else {
                if (stripos($text, $fragment) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Convert a string to slug.
     */
    public static function slug(string $value, string $separator = '-'): string
    {
        $value = trim($value);

        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($transliterated !== false) {
                $value = $transliterated;
            }
        }

        $value = preg_replace('~[^\pL\pN]+~u', $separator, $value) ?? '';
        $value = trim($value, $separator);
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $value = preg_replace('~[^-a-z0-9]+~', '', $value) ?? '';

        return $value;
    }

    /**
     * Convert string to camelCase.
     */
    public static function camel(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);
        $value = lcfirst($value);
        return $value;
    }

    /**
     * Convert string to snake_case.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        if ($value === '') {
            return '';
        }
        $value = preg_replace('/\s+/u', '', ucwords(str_replace(['-', '_'], ' ', $value))) ?? '';
        $value = preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value) ?? '';
        return strtolower($value);
    }

    /**
     * Convert string to StudlyCase.
     */
    public static function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        return str_replace(' ', '', $value);
    }

    /**
     * Limit the number of characters in a string.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if ($length <= $limit) {
            return $value;
        }

        $substr = function_exists('mb_substr')
            ? mb_substr($value, 0, max(0, $limit), 'UTF-8')
            : substr($value, 0, max(0, $limit));

        return rtrim($substr) . $end;
    }

    /**
     * Limit the number of words in a string.
     */
    public static function words(string $value, int $words = 100, string $end = '...'): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $trimmed) ?: [];
        if (count($parts) <= $words) {
            return $trimmed;
        }

        $sliced = array_slice($parts, 0, $words);
        return implode(' ', $sliced) . $end;
    }

    /**
     * Generate a cryptographically secure random string.
     */
    public static function randomString(int $length = 16): string
    {
        if ($length <= 0) {
            return '';
        }

        $bytes = random_bytes((int) ceil($length * 0.75));
        $base64 = rtrim(strtr(base64_encode($bytes), '+/', 'AZ'), '=');
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $base64) ?? '';
        if (strlen($clean) < $length) {
            $clean .= bin2hex(random_bytes(max(1, $length - strlen($clean))));
        }
        return substr($clean, 0, $length);
    }

    /**
     * Generate a UUID v4 string.
     */
    public static function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        $hex = bin2hex($data);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }

    /**
     * Check if a string is valid JSON.
     */
    public static function isJson(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        return json_validate($value);
    }

    /**
     * Normalize end-of-line characters to "\n".
     */
    public static function normalizeEol(string $value): string
    {
        return preg_replace("/(\r\n|\r)/", "\n", $value) ?? $value;
    }

    /**
     * Check if text is empty (optionally after trimming whitespace).
     */
    public static function isEmpty(?string $text, bool $trim = true): bool
    {
        if ($text === null) {
            return true;
        }
        return $trim ? trim($text) === '' : $text === '';
    }
}

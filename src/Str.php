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
     * Convert a string to lowercase, supporting locale-specific rules when possible.
     */
    public static function lower(string $value, ?string $lang = null): string
    {
        if ($value === '') {
            return '';
        }

        $transliterated = self::transliterateCase($value, 'lower', $lang);
        return $transliterated ?? self::defaultLower($value);
    }

    /**
     * Convert a string to uppercase, supporting locale-specific rules when possible.
     */
    public static function upper(string $value, ?string $lang = null): string
    {
        if ($value === '') {
            return '';
        }

        $transliterated = self::transliterateCase($value, 'upper', $lang);
        return $transliterated ?? self::defaultUpper($value);
    }

    /**
     * Convert a string to Title Case, preserving locale-sensitive characters when possible.
     */
    public static function title(string $value, ?string $lang = null): string
    {
        if ($value === '') {
            return '';
        }

        $transliterated = self::transliterateCase($value, 'title', $lang);
        if (is_string($transliterated)) {
            return $transliterated;
        }

        $lowered = self::lower($value, $lang);

        $titled = preg_replace_callback('/\pL+/u', function (array $match) use ($lang) {
            $word = $match[0];
            $first = self::unicodeSubstr($word, 0, 1);
            $rest = self::unicodeSubstr($word, 1);

            $firstUpper = self::upper($first, $lang);
            $restLower = $rest === '' ? '' : self::lower($rest, $lang);

            return $firstUpper . $restLower;
        }, $lowered);

        return is_string($titled) ? $titled : $lowered;
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

    /**
     * Get the part of the string before the first occurrence of search.
     */
    public static function before(string $text, string $search, bool $caseSensitive = true): string
    {
        if ($search === '') {
            return $text;
        }

        $pos = $caseSensitive ? strpos($text, $search) : stripos($text, $search);
        if ($pos === false) {
            return $text;
        }

        return substr($text, 0, $pos);
    }

    /**
     * Get the part of the string after the first occurrence of search.
     */
    public static function after(string $text, string $search, bool $caseSensitive = true): string
    {
        if ($search === '') {
            return $text;
        }

        $pos = $caseSensitive ? strpos($text, $search) : stripos($text, $search);
        if ($pos === false) {
            return $text;
        }

        return substr($text, $pos + strlen($search));
    }

    /**
     * Get the substring between two delimiters. Returns null if not found.
     */
    public static function between(string $text, string $from, string $to, bool $caseSensitive = true): ?string
    {
        if ($from === '' || $to === '') {
            return null;
        }

        $start = $caseSensitive ? strpos($text, $from) : stripos($text, $from);
        if ($start === false) {
            return null;
        }

        $startPos = $start + strlen($from);
        $end = $caseSensitive ? strpos($text, $to, $startPos) : stripos($text, $to, $startPos);
        if ($end === false || $end < $startPos) {
            return null;
        }

        return substr($text, $startPos, $end - $startPos);
    }

    /**
     * Replace the first occurrence of a substring.
     */
    public static function replaceFirst(string $text, string $search, string $replace, bool $caseSensitive = true): string
    {
        if ($search === '') {
            return $text;
        }

        $pos = $caseSensitive ? strpos($text, $search) : stripos($text, $search);
        if ($pos === false) {
            return $text;
        }

        return substr($text, 0, $pos) . $replace . substr($text, $pos + strlen($search));
    }

    /**
     * Replace the last occurrence of a substring.
     */
    public static function replaceLast(string $text, string $search, string $replace, bool $caseSensitive = true): string
    {
        if ($search === '') {
            return $text;
        }

        $pos = $caseSensitive ? strrpos($text, $search) : strripos($text, $search);
        if ($pos === false) {
            return $text;
        }

        return substr($text, 0, $pos) . $replace . substr($text, $pos + strlen($search));
    }

    /**
     * Determine if the string contains all given fragments.
     */
    public static function containsAll(string $text, array $needles, bool $caseSensitive = true): bool
    {
        foreach ($needles as $needle) {
            if ($needle === '') {
                continue;
            }
            if ($caseSensitive) {
                if (!str_contains($text, $needle)) {
                    return false;
                }
            } else {
                if (stripos($text, $needle) === false) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Ensure the string starts with the given prefix.
     */
    public static function ensurePrefix(string $text, string $prefix, bool $caseSensitive = true): string
    {
        if ($prefix === '' || self::startsWith($text, $prefix, $caseSensitive)) {
            return $text;
        }
        return $prefix . $text;
    }

    /**
     * Ensure the string ends with the given suffix.
     */
    public static function ensureSuffix(string $text, string $suffix, bool $caseSensitive = true): string
    {
        if ($suffix === '' || self::endsWith($text, $suffix, $caseSensitive)) {
            return $text;
        }
        return $text . $suffix;
    }

    /**
     * Collapse all whitespace into single spaces and trim the string.
     */
    public static function squish(string $text): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return '';
        }
        return preg_replace('/\s+/u', ' ', $trimmed) ?? $trimmed;
    }

    private static function defaultLower(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }

    private static function defaultUpper(string $value): string
    {
        return function_exists('mb_strtoupper')
            ? mb_strtoupper($value, 'UTF-8')
            : strtoupper($value);
    }

    private static function unicodeSubstr(string $value, int $start, ?int $length = null): string
    {
        if (function_exists('mb_substr')) {
            return $length === null
                ? mb_substr($value, $start, null, 'UTF-8')
                : mb_substr($value, $start, $length, 'UTF-8');
        }

        return $length === null ? substr($value, $start) : substr($value, $start, $length);
    }

    private static function transliterateCase(string $value, string $mode, ?string $lang): ?string
    {
        if (!class_exists(\Transliterator::class)) {
            return null;
        }

        $mode = strtolower($mode);
        $candidates = [];

        $localeId = self::normalizeLocaleForTransliterator($lang);
        if ($localeId !== null) {
            $candidates[] = $localeId . '-' . $mode;
            $candidates[] = $localeId . '_' . $mode;
        }

        $candidates[] = 'any-' . $mode;

        foreach ($candidates as $id) {
            $transliterator = @\Transliterator::create($id);
            if ($transliterator instanceof \Transliterator) {
                $result = $transliterator->transliterate($value);
                if (is_string($result)) {
                    return $result;
                }
            }
        }

        return null;
    }

    private static function normalizeLocaleForTransliterator(?string $lang): ?string
    {
        if ($lang === null) {
            return null;
        }

        $lang = trim($lang);
        if ($lang === '') {
            return null;
        }

        $lang = str_replace(['@', '#'], '', $lang);
        $lang = str_replace('-', '_', $lang);
        $lang = strtolower($lang);
        $parts = explode('_', $lang);

        return $parts[0] !== '' ? $parts[0] : null;
    }

   static function seoFileName($text)
    {
        $normalized = $text;
        $hasTransliteration = false;

        if (class_exists('Transliterator')) {
            $transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;', Transliterator::FORWARD);
            if ($transliterator instanceof Transliterator) {
                $result = $transliterator->transliterate($text);
                if (is_string($result)) {
                    $normalized = $result;
                    $hasTransliteration = true;
                }
            }
        }

        if (!$hasTransliteration) {
            $fallback = function_exists('iconv')
                ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text)
                : $text;
            if (!is_string($fallback)) {
                $fallback = $text;
            }
            $normalized = strtolower($fallback);
        }

        // Keep the dot (.) and word characters, replace other characters
        $normalized = preg_replace("/[^ \w\.]+/", "", $normalized);
        $normalized = str_replace(" ", "-", $normalized);
        // trim - and .
        $normalized = trim((string) $normalized, "-.");

        return $normalized;
    }

    static function seoUrl($text)
        {
        return self::seoFileName(str_replace('.', '', $text));
    }


    // Debug helpers
    // Usage: Str::prettyLog($var);
    static function prettyLog($v)
    {
        print ("<pre>" . print_r($v, true) . "</pre>");
    }

    

    static function prettyLogExit($v)
    {
        self::blackBG();
        self::prettyLog($v);
        exit;
    }

    static function print_functions($obj)
    {
        self::prettyLog(get_class_methods($obj));
    }

    static function blackBG()
    {
        echo "<!DOCTYPE html><html><head><style>body { background-color: black; color: #eee; }</style></head><body></body></html>";
    }
}

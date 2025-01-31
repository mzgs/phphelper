<?php

namespace PhpHelper\Utils;

class Helper
{
    public static function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    public static function slugify(string $text, string $separator = '-'): string
    {
        $text = preg_replace('~[^\pL\d]+~u', $separator, $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, $separator);
        $text = preg_replace('~-+~', $separator, $text);
        $text = strtolower($text);
        return $text ?: '';
    }

    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' sec';
        }

        $minutes = floor($seconds / 60);
        $hours   = floor($minutes / 60);
        $days    = floor($hours / 24);

        if ($days > 0) {
            return $days . 'd ' . ($hours % 24) . 'h';
        }
        if ($hours > 0) {
            return $hours . 'h ' . ($minutes % 60) . 'm';
        }
        return $minutes . 'm ' . ($seconds % 60) . 's';
    }

    public static function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length - mb_strlen($suffix)) . $suffix;
    }

    public static function mask(string $string, int $start = 0, ?int $length = null, string $mask = '*'): string
    {
        $strLen     = mb_strlen($string);
        $length     = $length ?? $strLen - $start;
        $maskLength = min($length, $strLen - $start);

        return
            mb_substr($string, 0, $start) .
            str_repeat($mask, $maskLength) .
            mb_substr($string, $start + $maskLength);
    }

    public static function generateRandomString(
        int $length = 16,
        string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
    ): string {
        $charLength = strlen($characters);
        $random     = '';

        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[random_int(0, $charLength - 1)];
        }

        return $random;
    }

    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public static function sanitizeString(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function extractUrls(string $text): array
    {
        preg_match_all(
            '/\b(?:https?:\/\/|www\.)[^\s<>\[\]{}"\']++/',
            $text,
            $matches
        );
        return $matches[0];
    }

    public static function getClientIp(): ?string
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!isset($_SERVER[$header])) {
                continue;
            }

            $ip = filter_var($_SERVER[$header], FILTER_VALIDATE_IP);
            if ($ip !== false) {
                return $ip;
            }
        }

        return null;
    }

    public static function isAjaxRequest(): bool
    {
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }

    public static function isSsl(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
    }

    public static function redirectTo(string $url, int $statusCode = 302): void
    {
        header('Location: ' . $url, true, $statusCode);
        exit();
    }

    public static function getCurrentUrl(bool $withQueryString = true): string
    {
        $protocol = self::isSsl() ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        $uri      = $_SERVER['REQUEST_URI'];

        if (!$withQueryString) {
            $uri = explode('?', $uri)[0];
        }

        return "{$protocol}://{$host}{$uri}";
    }

    public static function parseUserAgent(): array
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return [
            'browser' => self::getBrowserFromUserAgent($userAgent),
            'os'      => self::getOsFromUserAgent($userAgent),
            'device'  => self::getDeviceFromUserAgent($userAgent),
        ];
    }

    private static function getBrowserFromUserAgent(string $userAgent): string
    {
        $browsers = [
            'Chrome'  => '/chrome/i',
            'Firefox' => '/firefox/i',
            'Safari'  => '/safari/i',
            'Opera'   => '/opera|OPR/i',
            'Edge'    => '/edg/i',
            'IE'      => '/msie|trident/i',
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'Unknown';
    }

    private static function getOsFromUserAgent(string $userAgent): string
    {
        $os = [
            'Windows' => '/windows|win32/i',
            'Mac'     => '/macintosh|mac os x/i',
            'Linux'   => '/linux/i',
            'Android' => '/android/i',
            'iOS'     => '/iphone|ipad|ipod/i',
        ];

        foreach ($os as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return 'Unknown';
    }

    private static function getDeviceFromUserAgent(string $userAgent): string
    {
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            return 'Tablet';
        }

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            return 'Mobile';
        }

        return 'Desktop';
    }
}
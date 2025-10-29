<?php

namespace PhpHelper;

class Http
{
    /**
     * Redirect to a URL with optional status code.
     */
    public static function redirect(string $url, int $statusCode = 302, bool $exit = true): void
    {
        if (!headers_sent()) {
            header('Location: ' . $url, true, $statusCode);
        } else {
            echo '<script>window.location.href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '";</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        }

        if ($exit) {
            exit();
        }
    }

    /**
     * Stream a file as a download. Returns false if file is missing, otherwise exits.
     * Note: No return type because successful paths end the request with exit().
     */
    public static function download(string $filename, string $mimetype = 'application/octet-stream')
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }
        $base = basename($filename);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Disposition: attachment; filename=' . $base);
        header('Content-Length: ' . filesize($filename));
        header('Content-Type: ' . $mimetype);
        readfile($filename);
        exit();
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            foreach ($headers as $k => $v) {
                header($k . ': ' . $v, true);
            }
        }
        echo class_exists('Format') ? Format::json($data) : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    public static function text(string $data, int $status = 200, array $headers = []): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/plain; charset=utf-8');
            foreach ($headers as $k => $v) {
                header($k . ': ' . $v, true);
            }
        }
        echo $data;
        exit();
    }

    static function html(string $data, int $status = 200, array $headers = []): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/html; charset=utf-8');
            foreach ($headers as $k => $v) {
                header($k . ': ' . $v, true);
            }
        }
        echo $data;
        exit();
    }

    static function parseJsonRequest(): mixed
    {
        $input = file_get_contents('php://input');
        if ($input === false || $input === '') {
            return null;
        }
        
        if (json_validate($input)) {
            return json_decode($input, true);
        }  
        return null;
    }

    /**
     * Collect best-effort client information (IP, UA, browser, OS, device, language, request).
     *
     * Data is derived from standard server variables and common proxy headers.
     * Values are best-effort and not trusted; do your own validation when needed.
     *
     * @return array{
     *   ip:string,
     *   ips:array<int,string>,
     *   is_proxy:bool,
     *   user_agent:string,
     *   browser:string,
     *   browser_version:string,
     *   os:string,
     *   engine:string,
     *   device:string,
     *   is_mobile:bool,
     *   is_tablet:bool,
     *   is_desktop:bool,
     *   is_bot:bool,
     *   accept_language:string,
     *   languages:array<int,string>,
     *   accept:string,
     *   referer:string,
     *   method:string,
     *   scheme:string,
     *   host:string,
     *   port:int,
     *   path:string,
     *   query:string,
     *   url:string
     * }
     */
    public static function clientInfo(): array
    {
        $server = $_SERVER ?? [];

        // Scheme (consider proxies)
        $scheme = 'http';
        if ((isset($server['HTTPS']) && ($server['HTTPS'] === 'on' || $server['HTTPS'] === '1'))
            || (isset($server['REQUEST_SCHEME']) && $server['REQUEST_SCHEME'] === 'https')
            || (isset($server['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$server['HTTP_X_FORWARDED_PROTO']) === 'https')
        ) {
            $scheme = 'https';
        }

        // Host/port
        $host = (string)($server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? '');
        $port = (int)($server['SERVER_PORT'] ?? ($scheme === 'https' ? 443 : 80));

        // Request URI
        $requestUri = (string)($server['REQUEST_URI'] ?? '');
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '';
        $query = (string)(parse_url($requestUri, PHP_URL_QUERY) ?? '');

        // Full URL
        $hostForUrl = $host;
        $defaultPort = $scheme === 'https' ? 443 : 80;
        if ($port && $port !== $defaultPort && strpos($host, ':') === false) {
            $hostForUrl .= ':' . $port;
        }
        $url = ($hostForUrl !== '' ? ($scheme . '://' . $hostForUrl) : '') . $requestUri;

        // Method
        $method = (string)($server['REQUEST_METHOD'] ?? '');

        // Headers (subset)
        $ua = (string)($server['HTTP_USER_AGENT'] ?? '');
        $acceptLang = (string)($server['HTTP_ACCEPT_LANGUAGE'] ?? '');
        $accept = (string)($server['HTTP_ACCEPT'] ?? '');
        $referer = (string)($server['HTTP_REFERER'] ?? '');

        // IPs (consider common proxy headers). Keep as-is (may include private ranges in dev).
        [$ip, $ips, $isProxy] = self::detectIpChain($server);

        // Browser/OS/engine and device classification
        [$browser, $browserVersion, $os, $engine] = self::parseUaBasics($ua);
        [$device, $isMobile, $isTablet, $isDesktop, $isBot] = self::classifyDevice($ua);

        // Languages (sorted by q-value). Return list of tags, top at index 0.
        $languages = self::parseAcceptLanguage($acceptLang);

        return [
            'ip' => $ip,
            'ips' => $ips,
            'is_proxy' => $isProxy,

            'user_agent' => $ua,
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'os' => $os,
            'engine' => $engine,

            'device' => $device,
            'is_mobile' => $isMobile,
            'is_tablet' => $isTablet,
            'is_desktop' => $isDesktop,
            'is_bot' => $isBot,

            'accept_language' => $acceptLang,
            'languages' => $languages,
            'accept' => $accept,
            'referer' => $referer,

            'method' => $method,
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'path' => $path,
            'query' => $query,
            'url' => $url,
        ];
    }

    // ---- Internals -------------------------------------------------------

    /**
     * @param array<string,mixed> $server
     * @return array{0:string,1:array<int,string>,2:bool}
     */
    private static function detectIpChain(array $server): array
    {
        $candidates = [];

        // RFC 7239 Forwarded: for=1.2.3.4;proto=https;by=...
        if (!empty($server['HTTP_FORWARDED'])) {
            $forwarded = (string)$server['HTTP_FORWARDED'];
            foreach (explode(',', $forwarded) as $part) {
                if (preg_match('/for=\"?\[?([a-fA-F0-9:\\.]+)\]?/i', $part, $m)) {
                    $candidates[] = $m[1];
                }
            }
        }

        // X-Forwarded-For: client, proxy1, proxy2
        if (!empty($server['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(',', (string)$server['HTTP_X_FORWARDED_FOR']) as $xip) {
                $candidates[] = trim($xip);
            }
        }

        // Other common headers
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_REAL_IP'] as $h) {
            if (!empty($server[$h])) {
                $candidates[] = (string)$server[$h];
            }
        }

        // Always include REMOTE_ADDR last
        if (!empty($server['REMOTE_ADDR'])) {
            $candidates[] = (string)$server['REMOTE_ADDR'];
        }

        // Normalize, validate, dedupe (preserve order)
        $seen = [];
        $ips = [];
        foreach ($candidates as $cand) {
            $ip = trim($cand);
            // strip port if present (IPv4:port or [IPv6]:port)
            if (preg_match('/^\[(.*)\]:(\d+)$/', $ip, $m)) {
                $ip = $m[1];
            } elseif (preg_match('/^(.*):(\d+)$/', $ip, $m) && substr_count($ip, ':') === 1) {
                $ip = $m[1];
            }

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                if (!isset($seen[$ip])) {
                    $seen[$ip] = true;
                    $ips[] = $ip;
                }
            }
        }

        $primary = $ips[0] ?? '';
        $isProxy = isset($server['HTTP_X_FORWARDED_FOR']) || isset($server['HTTP_FORWARDED']) || isset($server['HTTP_X_REAL_IP']) || isset($server['HTTP_CLIENT_IP']);
        return [$primary, $ips, (bool)$isProxy];
    }

    /**
     * @return array{0:string,1:string,2:string,3:string} browser, version, os, engine
     */
    private static function parseUaBasics(string $ua): array
    {
        $browser = 'Unknown';
        $version = '';
        $os = 'Unknown';
        $engine = 'Unknown';

        // Prefer get_browser when browscap is configured
        $info = @get_browser(null, true);
        if (is_array($info) && !empty($info)) {
            $browser = trim((string)($info['browser'] ?? $browser));
            $version = trim((string)($info['version'] ?? $version));
            $os = trim((string)($info['platform'] ?? $os));
        }

        // Engine
        if ($ua !== '') {
            if (stripos($ua, 'AppleWebKit') !== false) {
                $engine = 'WebKit';
                if (stripos($ua, 'Chrome') !== false || stripos($ua, 'Chromium') !== false) {
                    $engine = 'Blink';
                }
            } elseif (stripos($ua, 'Gecko/') !== false && stripos($ua, 'like Gecko') === false) {
                $engine = 'Gecko';
            } elseif (stripos($ua, 'Trident/') !== false || stripos($ua, 'MSIE') !== false) {
                $engine = 'Trident';
            }
        }

        // Browser + version (order matters) — fallback parsing
        $patterns = [
            'Edge' => '/Edg\/([\\d\\.]+)/',
            'Opera' => '/OPR\/([\\d\\.]+)/',
            'Vivaldi' => '/Vivaldi\/([\\d\\.]+)/',
            'Brave' => '/Brave\/([\\d\\.]+)/',
            'Chrome' => '/Chrome\/([\\d\\.]+)/',
            'Firefox' => '/Firefox\/([\\d\\.]+)/',
            'Safari' => '/Version\/([\\d\\.]+) Safari\//',
            'IE' => '/MSIE ([\\d\\.]+);/'
        ];
        if ($browser === 'Unknown') {
            foreach ($patterns as $name => $regex) {
                if (preg_match($regex, $ua, $m)) {
                    $browser = $name;
                    $version = $m[1] ?? '';
                    break;
                }
            }
        }

        // OS — fallback parsing
        $oses = [
            'Windows' => '/Windows NT/',
            'macOS' => '/Mac OS X/',
            'iOS' => '/iPhone|iPad|iPod/',
            'Android' => '/Android/',
            'Linux' => '/Linux/',
            'ChromeOS' => '/CrOS/'
        ];
        if ($os === 'Unknown') {
            foreach ($oses as $name => $regex) {
                if (preg_match($regex, $ua)) {
                    $os = $name;
                    break;
                }
            }
        }

        return [$browser, $version, $os, $engine];
    }

    /**
        * @return array{0:string,1:bool,2:bool,3:bool,4:bool} device, isMobile, isTablet, isDesktop, isBot
        */
    private static function classifyDevice(string $ua): array
    {
        $uaLower = strtolower($ua);
        $isBot = $uaLower !== '' && (bool)preg_match('/bot|crawl|spider|slurp|bingpreview|yandex|baidu|duckduck|ahrefs|semrush|facebookexternalhit|crawler/i', $ua);
        $isTablet = (bool)preg_match('/iPad|Tablet|Nexus 7|Nexus 9|Nexus 10|SM-T|GT-P|Kindle|Silk|PlayBook|Tab/i', $ua);
        $isMobile = !$isTablet && (bool)preg_match('/Mobile|iPhone|Android(?!.*Tablet)|Windows Phone|IEMobile|Opera Mini|BlackBerry|BB10/i', $ua);
        $isDesktop = !$isMobile && !$isTablet && !$isBot;

        $device = 'unknown';
        if ($isBot) {
            $device = 'bot';
        } elseif ($isTablet) {
            $device = 'tablet';
        } elseif ($isMobile) {
            $device = 'mobile';
        } elseif ($isDesktop) {
            $device = 'desktop';
        }

        return [$device, $isMobile, $isTablet, $isDesktop, $isBot];
    }

    /**
     * Parse Accept-Language into a list of tags ordered by quality.
     * @return array<int,string>
     */
    private static function parseAcceptLanguage(string $header): array
    {
        if ($header === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $header));
        $langs = [];
        foreach ($parts as $part) {
            $q = 1.0;
            $tag = $part;
            if (str_contains($part, ';')) {
                [$tag, $params] = array_map('trim', explode(';', $part, 2));
                if (preg_match('/q=([0-9]*\.?[0-9]+)/', $params, $m)) {
                    $q = (float)$m[1];
                }
            }
            if ($tag !== '') {
                $langs[] = ['tag' => $tag, 'q' => $q];
            }
        }

        usort($langs, function ($a, $b) {
            return ($b['q'] <=> $a['q']);
        });

        return array_values(array_map(static function ($row) {
            return (string)$row['tag'];
        }, $langs));
    }

}

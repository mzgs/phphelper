<?php

class Client
{
    /**
     * Best-effort browser information with fallback when browscap is not configured.
     *
     * @return array{ip:string,browser:string,os:string}
     */
    public static function browserInfo(): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Try using get_browser (requires browscap config). Suppress warnings and handle failure.
        $info = @get_browser(null, true);
        if (is_array($info)) {
            $browser = trim(($info['browser'] ?? 'Unknown') . ' ' . ($info['version'] ?? ''));
            $os = $info['platform'] ?? 'Unknown';
            return ['ip' => $ip, 'browser' => $browser, 'os' => $os];
        }

        // Fallback: minimal user-agent parsing
        $browser = 'Unknown';
        $version = '';
        $os = 'Unknown';

        $patterns = [
            'Edge' => '/Edg\/([\d\.]+)/',
            'Opera' => '/OPR\/([\d\.]+)/',
            'Chrome' => '/Chrome\/([\d\.]+)/',
            'Firefox' => '/Firefox\/([\d\.]+)/',
            'Safari' => '/Version\/([\d\.]+) Safari/',
            'IE' => '/MSIE ([\d\.]+);/',
        ];
        foreach ($patterns as $name => $regex) {
            if (preg_match($regex, $ua, $m)) {
                $browser = $name;
                $version = $m[1];
                break;
            }
        }

        $oses = [
            'Windows' => '/Windows NT/',
            'macOS' => '/Mac OS X/',
            'Linux' => '/Linux/',
            'Android' => '/Android/',
            'iOS' => '/iPhone|iPad/',
        ];
        foreach ($oses as $name => $regex) {
            if (preg_match($regex, $ua)) {
                $os = $name;
                break;
            }
        }

        return ['ip' => $ip, 'browser' => trim($browser . ' ' . $version), 'os' => $os];
    }
}


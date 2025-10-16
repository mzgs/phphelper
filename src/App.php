<?php

class App
{
    public static function isLocal(): bool
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        $host = strtok($host, ':') ?: $host;

        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return true;
        }

        $ips = [
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['SERVER_ADDR'] ?? null,
        ];

        foreach ($ips as $ip) {
            if (!is_string($ip) || $ip === '') {
                continue;
            }

            if ($ip === '127.0.0.1' || $ip === '::1') {
                return true;
            }
        }

        return false;
    }

    public static function isCli(): bool
    {
        return in_array(PHP_SAPI, ['cli', 'phpdbg'], true);
    }

    public static function isProduction(): bool
    {
        return !self::isLocal() && !self::isCli();
    }
}

<?php

namespace PhpHelper;

use DateTimeImmutable;

class CacheStore
{
    private const DEFAULT_SCOPE = 'general';

    private function __construct()
    {
    }

    public static function remember(string $key, int $ttlSeconds, callable $callback, ?string $scope = null)
    {
        $cached = self::get($key, $scope);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        self::put($key, $value, $ttlSeconds, $scope);

        return $value;
    }

    public static function get(string $key, ?string $scope = null)
    {
        $scope = $scope ?: self::DEFAULT_SCOPE;

        $row = DB::getRow('SELECT payload, expires_at FROM cache_store WHERE cache_key = ? AND scope = ?', [$key, $scope]) ?: null;
        if (!$row) {
            return null;
        }

        $expiresAt = $row['expires_at'] ?? null;
        if ($expiresAt) {
            $expires = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $expiresAt);
            if ($expires && $expires->getTimestamp() < time()) {
                self::delete($key, $scope);
                return null;
            }
        }

        $payload = $row['payload'] ?? null;
        if ($payload === null || $payload === '') {
            return null;
        }

        $decoded = json_decode($payload, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $payload;
    }

    public static function put(string $key, $value, int $ttlSeconds, ?string $scope = null): void
    {
        $scope     = $scope ?: self::DEFAULT_SCOPE;
        $expiresAt = $ttlSeconds > 0 ? date('Y-m-d H:i:s', time() + $ttlSeconds) : null;
        $payload   = json_encode($value);

        DB::query(
            'INSERT INTO cache_store (cache_key, scope, payload, expires_at) VALUES (?, ?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE payload = VALUES(payload), expires_at = VALUES(expires_at), updated_at = NOW()',
            [$key, $scope, $payload, $expiresAt]
        );
    }

    public static function delete(string $key, ?string $scope = null): void
    {
        $scope = $scope ?: self::DEFAULT_SCOPE;
        DB::delete('cache_store', 'cache_key = :cache_key AND scope = :scope', [
            'cache_key' => $key,
            'scope'     => $scope,
        ]);
    }

    public static function clearScope(?string $scope = null): void
    {
        $scope = $scope ?: self::DEFAULT_SCOPE;
        DB::delete('cache_store', 'scope = :scope', ['scope' => $scope]);
    }

    public static function pruneExpired(): void
    {
        DB::delete('cache_store', 'expires_at IS NOT NULL AND expires_at < NOW()');
    }
}

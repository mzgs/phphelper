<?php


class Logs
{
    protected static string $table = 'logs';

    /** @var array<string, mixed> */
    protected static array $defaults = [];

    /**
     * Configure the logger.
     *
     * @param array{table?: string, defaults?: array<string, mixed>} $config
     */
    public static function configure(array $config = []): void
    {
        if (isset($config['table'])) {
            self::setTable($config['table']);
        }

        if (isset($config['defaults'])) {
            if (!is_array($config['defaults'])) {
                throw new InvalidArgumentException('The "defaults" option must be an array.');
            }
            self::setDefaults($config['defaults']);
        }
    }

    public static function setTable(string $table): void
    {
        $table = trim($table);
        if ($table === '') {
            throw new InvalidArgumentException('Table name cannot be empty.');
        }
        self::$table = $table;
    }

    /**
     * @param array<string, mixed> $defaults
     */
    public static function setDefaults(array $defaults): void
    {
        foreach ($defaults as $key => $_) {
            if (!is_string($key) || $key === '') {
                throw new InvalidArgumentException('Default keys must be non-empty strings.');
            }
        }

        self::$defaults = array_merge(self::$defaults, $defaults);
    }

    public static function clearDefaults(): void
    {
        self::$defaults = [];
    }

    /**
     * Persist a log entry and return its primary key.
     *
     * @param array<mixed> $context
     * @param array<mixed> $meta
     */
    public static function log(string $level, string $message, array $context = [], array $meta = []): string
    {
        $level = strtolower(trim($level));
        if ($level === '') {
            throw new InvalidArgumentException('Log level cannot be empty.');
        }

        $message = trim($message);
        if ($message === '') {
            throw new InvalidArgumentException('Log message cannot be empty.');
        }

        $record = array_merge(self::$defaults, [
            'level' => $level,
            'message' => $message,
            'context' => self::encodeOptional($context),
            'meta' => self::encodeOptional($meta),
        ]);


        if (!array_key_exists('created_at', $record)) {
            $record['created_at'] = self::timestamp();
        }

        return DB::insert(self::$table, self::removeNulls($record));
    }

    /**
     * Convenience wrappers for common log levels.
     *
     * @param array<mixed> $context
     * @param array<mixed> $meta
     */
    public static function debug(string $message, array $context = [], array $meta = []): string
    {
        return self::log('debug', $message, $context, $meta);
    }

    /** @param array<mixed> $context @param array<mixed> $meta */
    public static function info(string $message, array $context = [], array $meta = []): string
    {
        return self::log('info', $message, $context, $meta);
    }

    /** @param array<mixed> $context @param array<mixed> $meta */
    public static function notice(string $message, array $context = [], array $meta = []): string
    {
        return self::log('notice', $message, $context, $meta);
    }

    /** @param array<mixed> $context @param array<mixed> $meta */
    public static function warning(string $message, array $context = [], array $meta = []): string
    {
        return self::log('warning', $message, $context, $meta);
    }

    /** @param array<mixed> $context @param array<mixed> $meta */
    public static function error(string $message, array $context = [], array $meta = []): string
    {
        return self::log('error', $message, $context, $meta);
    }

    /** @param array<mixed> $context @param array<mixed> $meta */
    public static function critical(string $message, array $context = [], array $meta = []): string
    {
        return self::log('critical', $message, $context, $meta);
    }

    /** @param array<mixed> $context @param array<mixed> $meta */
    public static function alert(string $message, array $context = [], array $meta = []): string
    {
        return self::log('alert', $message, $context, $meta);
    }

    /** @param array<mixed> $context @param array<mixed> $meta */
    public static function emergency(string $message, array $context = [], array $meta = []): string
    {
        return self::log('emergency', $message, $context, $meta);
    }

    public static function createLogsTable(?string $table = null): void
    {
        $table ??= self::$table;
        $driver = self::driver();
        $quotedTable = DB::quoteIdentifier($table);

        if ($driver === 'mysql') {
            $levelIndex = DB::quoteIdentifier($table . '_level_created_at_idx');

            $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$quotedTable} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    level VARCHAR(32) NOT NULL,
    message LONGTEXT NOT NULL,
    context JSON NULL,
    meta JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX {$levelIndex} (level, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
            DB::execute($sql);
            return;
        }

        if ($driver === 'sqlite') {
            $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$quotedTable} (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    level TEXT NOT NULL,
    message TEXT NOT NULL,
    context TEXT NULL,
    meta TEXT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)
SQL;
            DB::execute($sql);

            $levelIndex = DB::quoteIdentifier($table . '_level_created_at_idx');

            DB::execute("CREATE INDEX IF NOT EXISTS {$levelIndex} ON {$quotedTable} (level, created_at)");
            return;
        }

        throw new RuntimeException('Logs::createLogsTable only supports MySQL and SQLite.');
    }

    protected static function driver(): string
    {
        return (string) DB::pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    protected static function timestamp(): string
    {
        return (new DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    /**
     * @param array<mixed> $value
     */
    protected static function encodeOptional(array $value): ?string
    {
        if ($value === []) {
            return null;
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Unable to encode value to JSON: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    protected static function removeNulls(array $record): array
    {
        return array_filter($record, static fn ($value) => $value !== null);
    }
}

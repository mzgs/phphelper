<?php

class Config
{
    protected static bool $initialized = false;
    protected static string $table = 'config';
    protected static string $keyColumn = 'config_key';
    protected static string $valueColumn = 'config_value';
    protected const MODIFIED_COLUMN = 'modified_at';

    /**
     * Initialise the config helper with optional table and column overrides.
     */
    public static function init(array $config = []): void
    {
        if (!DB::connected()) {
            throw new RuntimeException('DB::connect() (or a helper such as DB::sqlite()) must be called before Config::init().');
        }

        self::$initialized = false;
        self::$table = 'config';
        self::$keyColumn = 'config_key';
        self::$valueColumn = 'config_value';

        self::applyConfiguration($config);

        self::$initialized = true;
    }

    /**
     * Create the config table using the current or provided configuration.
     */
    public static function createConfigTable(array $options = []): void
    {
        self::ensureInitialized();

        if ($options !== []) {
            self::applyConfiguration($options);
        }

        $pdo = DB::pdo();
        $driver = strtolower((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

        $keyType = 'TEXT';
        $valueType = 'TEXT';
        $modifiedType = 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $suffix = '';

        if ($driver === 'mysql') {
            $keyType = 'VARCHAR(191)';
            $valueType = 'TEXT';
            $modifiedType = 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
            $suffix = ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        } elseif ($driver === 'pgsql') {
            $keyType = 'TEXT';
            $valueType = 'TEXT';
            $modifiedType = 'TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP';
        }

        $columns = [
            DB::quoteIdentifier(self::$keyColumn) . ' ' . $keyType . ' PRIMARY KEY NOT NULL',
            DB::quoteIdentifier(self::$valueColumn) . ' ' . $valueType,
            DB::quoteIdentifier(self::MODIFIED_COLUMN) . ' ' . $modifiedType,
        ];

        $sql = 'CREATE TABLE IF NOT EXISTS ' . DB::quoteIdentifier(self::$table)
             . " (\n    " . implode(",\n    ", $columns) . "\n)" . $suffix;

        $pdo->exec($sql);
    }

    /**
     * Store a configuration value.
     */
    public static function set(string $key, ?string $value): void
    {
        self::ensureInitialized();

        $key = self::normalizeKey($key);
        DB::upsert(
            self::$table,
            [
                self::$keyColumn => $key,
                self::$valueColumn => $value,
                self::MODIFIED_COLUMN => self::currentTimestamp(),
            ],
            self::$keyColumn
        );
    }

    /**
     * Retrieve a configuration value or default when missing.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        self::ensureInitialized();

        $key = self::normalizeKey($key);

        $sql = 'SELECT ' . DB::quoteIdentifier(self::$valueColumn)
             . ' FROM ' . DB::quoteIdentifier(self::$table)
             . ' WHERE ' . DB::quoteIdentifier(self::$keyColumn) . ' = :config_key'
             . ' LIMIT 1';

        $row = DB::getRow($sql, ['config_key' => $key]);
        if ($row === null) {
            return $default;
        }

        $value = $row[self::$valueColumn] ?? null;
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Determine whether a configuration key exists.
     */
    public static function has(string $key): bool
    {
        self::ensureInitialized();

        $key = self::normalizeKey($key);
        $where = DB::quoteIdentifier(self::$keyColumn) . ' = :config_key';

        return DB::count(self::$table, $where, ['config_key' => $key]) > 0;
    }

    /**
     * Remove a configuration value.
     */
    public static function delete(string $key): bool
    {
        self::ensureInitialized();

        $key = self::normalizeKey($key);
        $where = DB::quoteIdentifier(self::$keyColumn) . ' = :config_key';

        return DB::delete(self::$table, $where, ['config_key' => $key]) > 0;
    }

    /**
     * Retrieve all configuration values as an associative array.
     *
     * @return array<string, string|null>
     */
    public static function all(): array
    {
        self::ensureInitialized();

        $sql = 'SELECT ' . DB::quoteIdentifier(self::$keyColumn) . ', ' . DB::quoteIdentifier(self::$valueColumn)
             . ' FROM ' . DB::quoteIdentifier(self::$table)
             . ' ORDER BY ' . DB::quoteIdentifier(self::$keyColumn) . ' ASC';

        $rows = DB::getRows($sql);

        $result = [];
        foreach ($rows as $row) {
            $key = $row[self::$keyColumn] ?? null;
            $value = $row[self::$valueColumn] ?? null;

            if (!is_string($key)) {
                continue;
            }

            if ($value !== null && !is_string($value)) {
                $value = (string) $value;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Apply configuration overrides to the internal state.
     */
    protected static function applyConfiguration(array $config): void
    {
        if (isset($config['table'])) {
            self::$table = self::validateIdentifier((string) $config['table'], 'table');
        }

        if (isset($config['key_column'])) {
            self::$keyColumn = self::validateIdentifier((string) $config['key_column'], 'key column');
        }

        if (isset($config['value_column'])) {
            self::$valueColumn = self::validateIdentifier((string) $config['value_column'], 'value column');
        }
    }

    /**
     * Ensure the helper has been initialised and the DB connection is present.
     */
    protected static function ensureInitialized(): void
    {
        if (!self::$initialized) {
            throw new RuntimeException('Config::init() must be called before using the Config helper.');
        }

        if (!DB::connected()) {
            throw new RuntimeException('DB::connect() (or a helper such as DB::sqlite()) must be called before Config operations.');
        }
    }

    /**
     * Normalise and validate the configuration key.
     */
    protected static function normalizeKey(string $key): string
    {
        $key = trim($key);

        if ($key === '') {
            throw new InvalidArgumentException('Config key cannot be empty.');
        }

        return $key;
    }

    /**
     * Validate identifier inputs for table/column names.
     */
    protected static function validateIdentifier(string $name, string $label): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException('Invalid ' . $label . ' name: ' . $name);
        }

        return $name;
    }

    /**
     * Get the current UTC timestamp in second precision.
     */
    protected static function currentTimestamp(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}

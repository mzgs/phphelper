<?php

class DB
{
    /** @var PDO|null */
    protected static ?PDO $pdo = null;

    /**
     * Establish a PDO connection.
     */
    public static function connect(string $dsn, ?string $username = null, ?string $password = null, array $options = []): void
    {
        $defaults = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $opts = $options + $defaults;

        try {
            self::$pdo = new PDO($dsn, $username ?? '', $password ?? '', $opts);
        } catch (PDOException $e) {
            throw new RuntimeException('DB connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }
 
    /**
     * Determine if a connection is active.
     */
    public static function connected(): bool
    {
        return self::$pdo instanceof PDO;
    }

    /**
     * Get the underlying PDO instance.
     *
     * @throws RuntimeException when not connected
     */
    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new RuntimeException('DB is not connected. Call DB::connect() first.');
        }
        return self::$pdo;
    }

    /**
     * Disconnect the current PDO connection.
     */
    public static function disconnect(): void
    {
        self::$pdo = null;
    }

   
    /** Convert common truthy strings to bool. */
    protected static function toBool(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        $str = strtolower((string) $value);
        return in_array($str, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Prepare and execute a statement, returning the PDOStatement.
     *
     * @param array<string, mixed>|list<mixed> $params
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Execute a modifying statement (INSERT/UPDATE/DELETE) and return affected rows.
     *
     * @param array<string, mixed>|list<mixed> $params
     */
    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Fetch first row or null.
     *
     * @param array<string, mixed>|list<mixed> $params
     * @return array<string, mixed>|null
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Fetch all rows (associative arrays).
     *
     * @param array<string, mixed>|list<mixed> $params
     * @return list<array<string, mixed>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        /** @var list<array<string, mixed>> $all */
        $all = self::query($sql, $params)->fetchAll();
        return $all;
    }

    /**
     * Fetch a single scalar value (first column of first row) or null.
     *
     * @param array<string, mixed>|list<mixed> $params
     */
    public static function scalar(string $sql, array $params = []): mixed
    {
        $stmt = self::query($sql, $params);
        $value = $stmt->fetchColumn(0);
        return $value === false ? null : $value;
    }

    /**
     * Insert a row and return last insert id (string as per PDO contract).
     *
     * @param array<string, mixed> $data
     */
    public static function insert(string $table, array $data): string
    {
        if ($data === []) {
            throw new RuntimeException('Insert requires non-empty data.');
        }

        $cols = array_keys($data);
        $place = array_map(fn ($c) => ':' . $c, $cols);
        $quotedCols = array_map([self::class, 'quoteIdentifier'], $cols);

        $sql = 'INSERT INTO ' . self::quoteIdentifier($table)
             . ' (' . implode(', ', $quotedCols) . ' ) VALUES (' . implode(', ', $place) . ')';

        self::execute($sql, $data);
        return self::pdo()->lastInsertId();
    }

    /**
     * Update rows matching WHERE clause; returns affected row count.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed>|list<mixed> $params
     */
    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        if ($data === []) {
            return 0;
        }

        // Avoid param collisions by prefixing set params.
        $setParts = [];
        $execParams = [];
        foreach ($data as $col => $val) {
            $name = 'set_' . $col;
            $setParts[] = self::quoteIdentifier($col) . ' = :' . $name;
            $execParams[$name] = $val;
        }

        // Merge with where params
        foreach ($params as $k => $v) {
            $execParams[$k] = $v;
        }

        $sql = 'UPDATE ' . self::quoteIdentifier($table)
             . ' SET ' . implode(', ', $setParts)
             . ' WHERE ' . $where;

        return self::execute($sql, $execParams);
    }

    /**
     * Delete rows matching WHERE clause; returns affected row count.
     *
     * @param array<string, mixed>|list<mixed> $params
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = 'DELETE FROM ' . self::quoteIdentifier($table) . ' WHERE ' . $where;
        return self::execute($sql, $params);
    }

    /**
     * Begin a transaction.
     */
    public static function beginTransaction(): void
    {
        self::pdo()->beginTransaction();
    }

    /** Commit the transaction. */
    public static function commit(): void
    {
        self::pdo()->commit();
    }

    /** Roll back the transaction. */
    public static function rollBack(): void
    {
        self::pdo()->rollBack();
    }

    /**
     * Execute a callback inside a transaction. Commits on success, rolls back on exception.
     * Returns the callback's return value.
     *
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $started = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $started = true;
        }

        try {
            $result = $callback();
            if ($started) {
                $pdo->commit();
            }
            return $result;
        } catch (\Throwable $e) {
            if ($started && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Quote an identifier (table/column) for the active driver.
     * Handles simple dot-qualified names like schema.table or table.column.
     */
    public static function quoteIdentifier(string $identifier): string
    {
        $pdo = self::$pdo;
        $driver = $pdo ? (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) : '';

        $parts = explode('.', $identifier);

        $q = function (string $part) use ($driver): string {
            if ($part === '*') {
                return '*';
            }
            // Basic identifier escaping per driver
            return match ($driver) {
                'mysql' => '`' . str_replace('`', '``', $part) . '`',
                default => '"' . str_replace('"', '""', $part) . '"', // sqlite, pgsql, others
            };
        };

        return implode('.', array_map($q, $parts));
    }
}

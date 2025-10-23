<?php

namespace PhpHelper;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class DB
{
    /** @var PDO|null */
    protected static ?PDO $pdo = null;

    /**
     * Dump a MySQL database to a SQL file using mysqldump.
     *
     * @param array{
     *     host?: string,
     *     port?: int|string|null,
     *     unix_socket?: ?string,
     *     mysqldump_path?: string
     * } $options
     */
    public static function backup(string $dbname, string $dbUser, string $dbPassword, string $filePath = 'db.sql', array $options = []): void
    {
        if ($dbname === '') {
            throw new \InvalidArgumentException('Database name must not be empty.');
        }

        if ($filePath === '') {
            throw new \InvalidArgumentException('Backup file path must not be empty.');
        }

        $mysqldump = $options['mysqldump_path'] ?? 'mysqldump';
        if (!is_string($mysqldump) || $mysqldump === '') {
            throw new \InvalidArgumentException('The "mysqldump_path" option must be a non-empty string.');
        }

        $command = self::buildMysqlCliCommand($mysqldump, $dbUser, $dbPassword, $options)
            . ' ' . escapeshellarg($dbname)
            . ' > ' . escapeshellarg($filePath);

        self::runShellCommand($command, 'Database backup failed.');
    }

    /**
     * Restore a MySQL database from a SQL file.
     *
     * @param array{
     *     host?: string,
     *     port?: int|string|null,
     *     unix_socket?: ?string,
     *     charset?: string|null,
     *     mysql_path?: string
     * } $options
     */
    public static function restore(string $dbname, string $dbUser, string $dbPassword, string $filePath = 'db.sql', array $options = []): void
    {
        if ($dbname === '') {
            throw new \InvalidArgumentException('Database name must not be empty.');
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException('Backup file is not readable: ' . $filePath);
        }

        $mysql = $options['mysql_path'] ?? 'mysql';
        if (!is_string($mysql) || $mysql === '') {
            throw new \InvalidArgumentException('The "mysql_path" option must be a non-empty string.');
        }

        $charset = $options['charset'] ?? 'utf8';
        if ($charset !== null) {
            if (!is_string($charset) || $charset === '') {
                throw new \InvalidArgumentException('The "charset" option must be a non-empty string or null.');
            }
            if (!preg_match('/^[A-Za-z0-9_]+$/', $charset)) {
                throw new \InvalidArgumentException('Invalid charset supplied for restore operation.');
            }
        }

        $quotedDb = self::quoteMysqlIdentifier($dbname);
        $mysqlBase = self::buildMysqlCliCommand($mysql, $dbUser, $dbPassword, $options);

        $dropSql = 'DROP DATABASE IF EXISTS ' . $quotedDb . ';';
        self::runShellCommand($mysqlBase . ' -e ' . escapeshellarg($dropSql), 'Failed to drop existing database.');

        $createSql = 'CREATE DATABASE ' . $quotedDb;
        if ($charset !== null) {
            $createSql .= ' DEFAULT CHARACTER SET ' . $charset;
        }
        $createSql .= ';';
        self::runShellCommand($mysqlBase . ' -e ' . escapeshellarg($createSql), 'Failed to create database.');

        $importCommand = $mysqlBase . ' ' . escapeshellarg($dbname) . ' < ' . escapeshellarg($filePath);
        self::runShellCommand($importCommand, 'Database import failed.');
    }

    /**
     * Build CLI menu definitions for MySQL backups or restores using existing helpers.
     *
     * @param array{
     *     defaults?: array{
     *         database?: string,
     *         user?: string,
     *         password?: string,
     *         file?: string,
     *         host?: string,
     *         port?: int|string|null,
     *         unix_socket?: string|null,
     *         charset?: string|null
     *     },
     *     backup_options?: array<string, mixed>,
     *     restore_options?: array<string, mixed>,
     *     mode?: string
     * } $options
     *
     * @return array<int, array{label: string, run: callable}>
     */
    public static function cliBackupRestoreOptions(string $dbname, string $dbUser, string $dbPassword, array $options = []): array
    {
        if (!App::isCli()) {
            return [];
        }

        if (isset($options['defaults']) && !is_array($options['defaults'])) {
            throw new \InvalidArgumentException('The \"defaults\" option must be an array when provided.');
        }

        if (isset($options['mode']) && !is_string($options['mode'])) {
            throw new \InvalidArgumentException('The \"mode\" option must be a string when provided.');
        }

        $defaults = array_merge([
            'database'    => $dbname,
            'user'        => $dbUser,
            'password'    => $dbPassword,
            'file'        => 'db.sql',
            'host'        => '',
            'port'        => '',
            'unix_socket' => '',
            'charset'     => 'utf8',
        ], $options['defaults'] ?? []);

        $stringDefault = static function (array $values, string $key, ?string $fallback = null): ?string {
            if (!array_key_exists($key, $values)) {
                return $fallback;
            }
            $value = $values[$key];
            if ($value === null) {
                return $fallback;
            }
            $value = (string) $value;
            if ($value === '') {
                return $fallback;
            }
            return $value;
        };

        $database = $stringDefault($defaults, 'database', $dbname);
        if ($database === null || $database === '') {
            throw new \InvalidArgumentException('Database name is required.');
        }

        $user = $stringDefault($defaults, 'user', $dbUser);
        if ($user === null || $user === '') {
            throw new \InvalidArgumentException('Database user is required.');
        }

        $password = $stringDefault($defaults, 'password', $dbPassword) ?? '';

        $filePath = $stringDefault($defaults, 'file', 'db.sql');
        if ($filePath === null || $filePath === '') {
            throw new \InvalidArgumentException('File path is required.');
        }

        $host = $stringDefault($defaults, 'host', '');
        $portValue = $stringDefault($defaults, 'port', '');
        $socket = $stringDefault($defaults, 'unix_socket', '');

        $cliOptions = [];
        if ($host !== null && $host !== '') {
            $cliOptions['host'] = $host;
        }
        if ($portValue !== null && $portValue !== '') {
            $cliOptions['port'] = ctype_digit($portValue) ? (int) $portValue : $portValue;
        }
        if ($socket !== null && $socket !== '') {
            $cliOptions['unix_socket'] = $socket;
        }

        $modeValue = $options['mode'] ?? null;
        $mode = null;
        if (is_string($modeValue)) {
            $mode = strtolower(trim($modeValue));
        }
        if ($mode !== null && $mode !== '' && !in_array($mode, ['backup', 'restore'], true)) {
            throw new \InvalidArgumentException('Mode must be either "backup" or "restore" when provided.');
        }

        if (isset($options['backup_options']) && !is_array($options['backup_options'])) {
            throw new \InvalidArgumentException('The \"backup_options\" configuration must be an array when provided.');
        }
        if (isset($options['restore_options']) && !is_array($options['restore_options'])) {
            throw new \InvalidArgumentException('The \"restore_options\" configuration must be an array when provided.');
        }

        $backupOptions = $options['backup_options'] ?? [];
        $restoreOptions = $options['restore_options'] ?? [];

        $backupCliOptions = array_merge($cliOptions, $backupOptions);
        $mysqldumpPath = $stringDefault($backupOptions, 'mysqldump_path');
        if ($mysqldumpPath !== null && $mysqldumpPath !== '') {
            $backupCliOptions['mysqldump_path'] = $mysqldumpPath;
        }

        $restoreCliOptions = array_merge($cliOptions, $restoreOptions);
        $mysqlPath = $stringDefault($restoreOptions, 'mysql_path');
        if ($mysqlPath !== null && $mysqlPath !== '') {
            $restoreCliOptions['mysql_path'] = $mysqlPath;
        }

        $charsetDefault = $stringDefault($defaults, 'charset');
        if ($charsetDefault === null) {
            $charsetDefault = $stringDefault($restoreOptions, 'charset', 'utf8');
        }
        if ($charsetDefault !== null && $charsetDefault !== '') {
            $restoreCliOptions['charset'] = $charsetDefault;
        }

        $write = static function (string $message): void {
            $stdout = defined('STDOUT') && is_resource(STDOUT) ? STDOUT : fopen('php://stdout', 'w');
            if (!is_resource($stdout)) {
                throw new RuntimeException('Unable to access CLI output.');
            }

            $shouldClose = !defined('STDOUT') || $stdout !== STDOUT;
            try {
                fwrite($stdout, $message);
            } finally {
                if ($shouldClose) {
                    fclose($stdout);
                }
            }
        };

        $title = PHP_EOL . '=== Database Backup/Restore (' . $database . ') ===' . PHP_EOL;

        $entries = [];
        $modes = $mode !== null && $mode !== '' ? [$mode] : ['backup', 'restore'];

        foreach ($modes as $modeEntry) {
            if ($modeEntry === 'backup') {
                $entries[] = [
                    'label' => 'Backup database to SQL file',
                    'run' => function () use ($write, $title, $database, $user, $password, $filePath, $backupCliOptions): int {
                        $write($title);
                        $write('Mode: Backup' . PHP_EOL);
                        self::backup($database, $user, $password, $filePath, $backupCliOptions);
                        $write('Backup completed successfully to ' . $filePath . PHP_EOL);

                        return 0;
                    },
                ];
            } elseif ($modeEntry === 'restore') {
                $entries[] = [
                    'label' => 'Restore database from SQL file',
                    'run' => function () use ($write, $title, $database, $user, $password, $filePath, $restoreCliOptions): int {
                        $write($title);
                        $write('Mode: Restore' . PHP_EOL);
                        self::restore($database, $user, $password, $filePath, $restoreCliOptions);
                        $write('Restore completed successfully from ' . $filePath . PHP_EOL);

                        return 0;
                    },
                ];
            }
        }

        return $entries;
    }

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
     * Connect to a MySQL or MariaDB database using common defaults.
     *
     * @param array{
     *     host?: string,
     *     port?: int|string|null,
     *     charset?: ?string,
     *     unix_socket?: ?string,
     *     attributes?: array<int|string, mixed>
     * } $options
     */
    public static function mysql(string $dbname, ?string $username = null, ?string $password = null, array $options = []): void
    {
        $dsnParts = ['mysql:dbname=' . $dbname];

        $unixSocket = $options['unix_socket'] ?? null;
        if ($unixSocket !== null) {
            if (!is_string($unixSocket)) {
                throw new \InvalidArgumentException('The "unix_socket" option must be a string or null.');
            }
            $dsnParts[] = 'unix_socket=' . $unixSocket;
        } else {
            $host = $options['host'] ?? '127.0.0.1';
            if (!is_string($host)) {
                throw new \InvalidArgumentException('The "host" option must be a string.');
            }
            $dsnParts[] = 'host=' . $host;

            if (array_key_exists('port', $options) && $options['port'] !== null) {
                if (!is_int($options['port']) && !is_string($options['port'])) {
                    throw new \InvalidArgumentException('The "port" option must be an int, string, or null.');
                }
                $dsnParts[] = 'port=' . $options['port'];
            }
        }

        $charset = $options['charset'] ?? 'utf8mb4';
        if ($charset !== null && $charset !== '') {
            if (!is_string($charset)) {
                throw new \InvalidArgumentException('The "charset" option must be a string or null.');
            }
            $dsnParts[] = 'charset=' . $charset;
        }

        $attributes = self::pdoAttributes($options);

        self::connect(implode(';', $dsnParts), $username, $password, $attributes);
    }

    /**
     * Connect to a PostgreSQL database.
     *
     * @param array{
     *     host?: string,
     *     port?: int|string|null,
     *     sslmode?: ?string,
     *     charset?: ?string,
     *     application_name?: ?string,
     *     attributes?: array<int|string, mixed>
     * } $options
     */
    public static function pgsql(string $dbname, ?string $username = null, ?string $password = null, array $options = []): void
    {
        $dsnParts = ['pgsql:dbname=' . $dbname];

        $host = $options['host'] ?? '127.0.0.1';
        if (!is_string($host)) {
            throw new \InvalidArgumentException('The "host" option must be a string.');
        }
        $dsnParts[] = 'host=' . $host;

        if (array_key_exists('port', $options) && $options['port'] !== null) {
            if (!is_int($options['port']) && !is_string($options['port'])) {
                throw new \InvalidArgumentException('The "port" option must be an int, string, or null.');
            }
            $dsnParts[] = 'port=' . $options['port'];
        }

        if (array_key_exists('sslmode', $options) && $options['sslmode'] !== null) {
            if (!is_string($options['sslmode'])) {
                throw new \InvalidArgumentException('The "sslmode" option must be a string or null.');
            }
            $dsnParts[] = 'sslmode=' . $options['sslmode'];
        }

        if (array_key_exists('charset', $options) && $options['charset'] !== null) {
            if (!is_string($options['charset'])) {
                throw new \InvalidArgumentException('The "charset" option must be a string or null.');
            }
            $dsnParts[] = 'options=--client_encoding=' . $options['charset'];
        }

        if (array_key_exists('application_name', $options) && $options['application_name'] !== null) {
            if (!is_string($options['application_name'])) {
                throw new \InvalidArgumentException('The "application_name" option must be a string or null.');
            }
            $dsnParts[] = 'application_name=' . $options['application_name'];
        }

        $attributes = self::pdoAttributes($options);

        self::connect(implode(';', $dsnParts), $username, $password, $attributes);
    }

    /**
     * Connect to a SQLite database (file or in-memory).
     *
     * @param array{
     *     memory?: bool,
     *     attributes?: array<int|string, mixed>
     * } $options
     */
    public static function sqlite(string $pathOrDsn = ':memory:', ?string $username = null, ?string $password = null, array $options = []): void
    {
        $dsn = 'sqlite:';

        $memory = $options['memory'] ?? null;
        if ($memory !== null && !is_bool($memory)) {
            throw new \InvalidArgumentException('The "memory" option must be a bool or null.');
        }

        if ($memory === true) {
            $dsn .= ':memory:';
        } elseif ($pathOrDsn === ':memory:') {
            $dsn .= ':memory:';
        } else {
            $dsn .= $pathOrDsn;
        }

        $attributes = self::pdoAttributes($options);

        self::connect($dsn, $username, $password, $attributes);
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
     * Build a mysql or mysqldump CLI command with shared connection parameters.
     *
     * @param array{
     *     host?: string,
     *     port?: int|string|null,
     *     unix_socket?: ?string
     * } $options
     */
    protected static function buildMysqlCliCommand(string $binary, string $dbUser, string $dbPassword, array $options = []): string
    {
        $command = 'MYSQL_PWD=' . escapeshellarg($dbPassword)
                 . ' ' . escapeshellarg($binary)
                 . ' -u' . escapeshellarg($dbUser);

        if (array_key_exists('host', $options) && $options['host'] !== null) {
            if (!is_string($options['host'])) {
                throw new \InvalidArgumentException('The "host" option must be a string or null.');
            }
            $command .= ' -h' . escapeshellarg($options['host']);
        }

        if (array_key_exists('port', $options) && $options['port'] !== null) {
            if (!is_int($options['port']) && !is_string($options['port'])) {
                throw new \InvalidArgumentException('The "port" option must be an int, string, or null.');
            }
            $command .= ' -P' . escapeshellarg((string) $options['port']);
        }

        if (array_key_exists('unix_socket', $options) && $options['unix_socket'] !== null) {
            if (!is_string($options['unix_socket'])) {
                throw new \InvalidArgumentException('The "unix_socket" option must be a string or null.');
            }
            $command .= ' --socket=' . escapeshellarg($options['unix_socket']);
        }

        return $command;
    }

    /** Execute a shell command and throw on failure. */
    protected static function runShellCommand(string $command, string $errorMessage): void
    {
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        if ($exitCode !== 0) {
            $details = $output === [] ? '' : ' Output: ' . implode("\n", $output);
            throw new RuntimeException($errorMessage . ' Exit code: ' . $exitCode . '.' . $details);
        }
    }

    /** Quote a MySQL identifier such as a database name. */
    protected static function quoteMysqlIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function pdoAttributes(array $options): array
    {
        if (!array_key_exists('attributes', $options)) {
            return [];
        }

        $attributes = $options['attributes'];
        if ($attributes === null) {
            return [];
        }

        if (!is_array($attributes)) {
            throw new \InvalidArgumentException('The "attributes" option must be an array of PDO attributes.');
        }

        return $attributes;
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
    public static function getRow(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function getById(string $table, string $id): ?array
    {
         return DB::getRow(
             'SELECT * FROM ' . DB::quoteIdentifier($table) . ' WHERE id = :id LIMIT 1',
             ['id' => $id]
         );
    }

    /**
     * Fetch all rows (associative arrays).
     *
     * @param array<string, mixed>|list<mixed> $params
     * @return list<array<string, mixed>>
     */
    public static function getRows(string $sql, array $params = []): array
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
    public static function getValue(string $sql, array $params = []): mixed
    {
        $stmt = self::query($sql, $params);
        $value = $stmt->fetchColumn(0);
        return $value === false ? null : $value;
    }

    /**
     * Count rows in a table with an optional WHERE clause.
     *
     * @param array<string, mixed>|list<mixed> $params
     */
    public static function count(string $table, string $where = '', array $params = []): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . self::quoteIdentifier($table);
        $where = trim($where);
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }

        $value = self::getValue($sql, $params);
        return (int) ($value ?? 0);
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
     * Insert or update a row using the database driver's native upsert syntax when possible.
     *
     * @param array<string, mixed> $data
     * @param list<string>|string $conflictColumns
     * @param list<string>|null $updateColumns
     */
    public static function upsert(string $table, array $data, array|string $conflictColumns, ?array $updateColumns = null): void
    {
        if ($data === []) {
            throw new RuntimeException('Upsert requires non-empty data.');
        }

        $conflictColumns = (array) $conflictColumns;
        if ($conflictColumns === []) {
            throw new RuntimeException('Upsert requires at least one conflict column.');
        }

        $columns = array_keys($data);
        $tableQuoted = self::quoteIdentifier($table);
        $quotedColumns = array_map([self::class, 'quoteIdentifier'], $columns);
        $placeholders = array_map(fn (string $col): string => ':' . $col, $columns);
        $params = $data;

        $updateColumns = $updateColumns ?? array_values(array_diff($columns, $conflictColumns));

        foreach ($conflictColumns as $column) {
            if (!array_key_exists($column, $data)) {
                throw new RuntimeException('Upsert conflict column missing from data: ' . $column);
            }
        }

        foreach ($updateColumns as $column) {
            if (!array_key_exists($column, $data)) {
                throw new RuntimeException('Upsert update column missing from data: ' . $column);
            }
        }

        $pdo = self::pdo();
        $driver = strtolower((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

        $sql = 'INSERT INTO ' . $tableQuoted
             . ' (' . implode(', ', $quotedColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        $quotedConflict = array_map([self::class, 'quoteIdentifier'], $conflictColumns);

        $updateAssignments = array_map(
            fn (string $column): string => self::quoteIdentifier($column),
            $updateColumns
        );

        switch ($driver) {
            case 'mysql':
                if ($updateAssignments === []) {
                    $noOp = $quotedConflict[0];
                    $sql .= ' ON DUPLICATE KEY UPDATE ' . $noOp . ' = ' . $noOp;
                } else {
                    $assignments = array_map(
                        fn (string $col): string => $col . ' = VALUES(' . $col . ')',
                        $updateAssignments
                    );
                    $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $assignments);
                }
                self::execute($sql, $params);
                return;

            case 'pgsql':
                $conflictExpr = '(' . implode(', ', $quotedConflict) . ')';
                if ($updateAssignments === []) {
                    $sql .= ' ON CONFLICT ' . $conflictExpr . ' DO NOTHING';
                } else {
                    $assignments = array_map(
                        fn (string $col): string => $col . ' = EXCLUDED.' . $col,
                        $updateAssignments
                    );
                    $sql .= ' ON CONFLICT ' . $conflictExpr . ' DO UPDATE SET ' . implode(', ', $assignments);
                }
                self::execute($sql, $params);
                return;

            case 'sqlite':
                $conflictExpr = '(' . implode(', ', $quotedConflict) . ')';
                if ($updateAssignments === []) {
                    $sql .= ' ON CONFLICT' . ' ' . $conflictExpr . ' DO NOTHING';
                } else {
                    $assignments = array_map(
                        fn (string $col): string => $col . ' = excluded.' . $col,
                        $updateAssignments
                    );
                    $sql .= ' ON CONFLICT ' . $conflictExpr . ' DO UPDATE SET ' . implode(', ', $assignments);
                }
                self::execute($sql, $params);
                return;

            default:
                self::transaction(function () use ($table, $data, $conflictColumns, $updateColumns): void {
                    $updateData = [];
                    foreach ($updateColumns as $column) {
                        $updateData[$column] = $data[$column];
                    }

                    $whereParts = [];
                    $whereParams = [];
                    foreach ($conflictColumns as $column) {
                        $paramName = 'conflict_' . preg_replace('/[^A-Za-z0-9_]/', '_', $column);
                        $whereParts[] = self::quoteIdentifier($column) . ' = :' . $paramName;
                        $whereParams[$paramName] = $data[$column];
                    }

                    $updated = 0;
                    if ($updateData !== []) {
                        $updated = self::update($table, $updateData, implode(' AND ', $whereParts), $whereParams);
                    }

                    if ($updated === 0) {
                        self::insert($table, $data);
                    }
                });
                return;
        }
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

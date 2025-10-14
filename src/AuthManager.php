<?php

class AuthManager
{
    protected static string $sessionKey = 'auth_user';
    protected static string $passwordField = 'password';
    protected static string $passwordCredentialKey = 'password';
    protected static bool $autoStartSession = true;
    protected static bool $regenerateOnLogin = true;
    protected static bool $regenerateOnLogout = true;

    protected static ?PDO $pdo = null;

    /** @var callable|null */
    protected static $userProvider = null;
    /** @var callable|null */
    protected static $userPersister = null;
    /** @var callable|null */
    protected static $passwordHasher = null;
    /** @var callable|null */
    protected static $passwordVerifier = null;

    public static function configure(array $options = []): void
    {
        if (array_key_exists('session_key', $options)) {
            self::$sessionKey = (string) $options['session_key'];
        }
        if (array_key_exists('password_field', $options)) {
            self::$passwordField = (string) $options['password_field'];
        }
        if (array_key_exists('password_credential_key', $options)) {
            self::$passwordCredentialKey = (string) $options['password_credential_key'];
        }
        if (array_key_exists('auto_start_session', $options)) {
            self::$autoStartSession = (bool) $options['auto_start_session'];
        }
        if (array_key_exists('regenerate_on_login', $options)) {
            self::$regenerateOnLogin = (bool) $options['regenerate_on_login'];
        }
        if (array_key_exists('regenerate_on_logout', $options)) {
            self::$regenerateOnLogout = (bool) $options['regenerate_on_logout'];
        }
        if (array_key_exists('user_provider', $options)) {
            self::setUserProvider($options['user_provider']);
        }
        if (array_key_exists('user_persister', $options)) {
            self::setUserPersister($options['user_persister']);
        }
        if (array_key_exists('pdo', $options)) {
            $pdoOption = $options['pdo'];
            if (!$pdoOption instanceof PDO) {
                throw new InvalidArgumentException('AuthManager configure option "pdo" must be an instance of PDO.');
            }
            self::setPdo($pdoOption);
        }
        if (array_key_exists('password_hasher', $options)) {
            $verifier = $options['password_verifier'] ?? null;
            self::setPasswordHasher($options['password_hasher'], $verifier);
        } elseif (array_key_exists('password_verifier', $options)) {
            self::setPasswordVerifier($options['password_verifier']);
        }
        if (array_key_exists('mysql', $options)) {
            $mysqlConfig = $options['mysql'];
            if (is_array($mysqlConfig) && array_key_exists('pdo', $mysqlConfig)) {
                $pdo = $mysqlConfig['pdo'];
                if (!$pdo instanceof PDO) {
                    throw new InvalidArgumentException('AuthManager mysql option "pdo" must be an instance of PDO.');
                }
                self::setPdo($pdo);
                unset($mysqlConfig['pdo']);
            }
            self::configureMysqlOption($mysqlConfig);
        }
    }

    public static function sessionKey(): string
    {
        return self::$sessionKey;
    }

    public static function setUserProvider(callable $provider): void
    {
        self::$userProvider = $provider;
    }

    public static function setUserPersister(callable $persister): void
    {
        self::$userPersister = $persister;
    }

    public static function setPdo(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function setPasswordHasher(callable $hasher, ?callable $verifier = null): void
    {
        self::$passwordHasher = $hasher;
        if ($verifier !== null) {
            self::$passwordVerifier = $verifier;
        }
    }

    public static function setPasswordVerifier(callable $verifier): void
    {
        self::$passwordVerifier = $verifier;
    }

    public static function attempt(array $credentials): bool
    {
        if (!is_callable(self::$userProvider)) {
            throw new RuntimeException('AuthManager user provider is not configured.');
        }

        $passwordKey = self::$passwordCredentialKey;
        if (!array_key_exists($passwordKey, $credentials)) {
            throw new InvalidArgumentException(sprintf('Credentials must include "%s".', $passwordKey));
        }

        $password = (string) $credentials[$passwordKey];
        unset($credentials[$passwordKey]);

        $provider = self::$userProvider;
        $user = $provider($credentials);
        if ($user === null) {
            return false;
        }

        $userData = self::castToArray($user);

        if (!array_key_exists(self::$passwordField, $userData)) {
            throw new RuntimeException(sprintf('User data must contain password field "%s" for verification.', self::$passwordField));
        }

        $hash = (string) $userData[self::$passwordField];
        if (!self::verifyPassword($password, $hash)) {
            return false;
        }

        self::login($userData);
        return true;
    }

    public static function login(mixed $user): void
    {
        $data = self::sanitizeUserArray(self::castToArray($user));

        self::ensureSession(true);

        if (self::$regenerateOnLogin && session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }

        $_SESSION[self::$sessionKey] = $data;
    }

    public static function logout(): void
    {
        self::ensureSession(true);

        unset($_SESSION[self::$sessionKey]);

        if (self::$regenerateOnLogout && session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }
    }

    public static function check(): bool
    {
        self::ensureSession(true);
        return isset($_SESSION[self::$sessionKey]) && is_array($_SESSION[self::$sessionKey]);
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function user(): ?array
    {
        self::ensureSession(true);

        $user = $_SESSION[self::$sessionKey] ?? null;
        if ($user === null) {
            return null;
        }
        if (!is_array($user)) {
            $user = self::castToArray($user);
            $_SESSION[self::$sessionKey] = $user;
        }
        return $user;
    }

    public static function id(string $field = 'id'): int|string|null
    {
        $user = self::user();
        if ($user === null) {
            return null;
        }
        return $user[$field] ?? null;
    }

    public static function updateUser(array $attributes, bool $merge = true): void
    {
        self::ensureSession(true);

        $current = $_SESSION[self::$sessionKey] ?? null;
        if (!is_array($current)) {
            throw new RuntimeException('No authenticated user to update.');
        }

        $updated = $merge ? array_merge($current, $attributes) : $attributes;
        $_SESSION[self::$sessionKey] = self::sanitizeUserArray($updated);
    }

    public static function register(array $attributes, bool $login = false): array
    {
        if (!is_callable(self::$userPersister)) {
            throw new RuntimeException('AuthManager user persister is not configured.');
        }

        $passwordField = self::$passwordField;
        if (!array_key_exists($passwordField, $attributes)) {
            throw new InvalidArgumentException(sprintf('Registration data must include "%s".', $passwordField));
        }

        $attributes[$passwordField] = self::hashPassword((string) $attributes[$passwordField]);

        $persister = self::$userPersister;
        $user = $persister($attributes);
        $userData = self::sanitizeUserArray(self::castToArray($user));

        if ($login) {
            self::login($userData);
        }

        return $userData;
    }

    public static function createUsersTable(array $options = []): void
    {
        $table = $options['table'] ?? 'users';
        if (!is_string($table) || $table === '') {
            throw new InvalidArgumentException('AuthManager createUsersTable option "table" must be a non-empty string.');
        }

        $idField = $options['id_field'] ?? 'id';
        if (!is_string($idField) || $idField === '') {
            throw new InvalidArgumentException('AuthManager createUsersTable option "id_field" must be a non-empty string.');
        }

        $emailField = $options['email_field'] ?? 'email';
        if (!is_string($emailField) || $emailField === '') {
            throw new InvalidArgumentException('AuthManager createUsersTable option "email_field" must be a non-empty string.');
        }

        $passwordField = $options['password_field'] ?? self::$passwordField;
        if (!is_string($passwordField) || $passwordField === '') {
            throw new InvalidArgumentException('AuthManager createUsersTable option "password_field" must be a non-empty string.');
        }

        $timestamps = array_key_exists('timestamps', $options)
            ? (bool) $options['timestamps']
            : true;

        $extra = $options['extra_columns'] ?? [];
        if (!is_array($extra)) {
            throw new InvalidArgumentException('AuthManager createUsersTable option "extra_columns" must be an array.');
        }

        $pdo = self::pdo();
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $quotedTable = self::quoteIdentifierWithPdo($pdo, $table);
        $quotedId = self::quoteIdentifierWithPdo($pdo, $idField);
        $quotedEmail = self::quoteIdentifierWithPdo($pdo, $emailField);
        $quotedPassword = self::quoteIdentifierWithPdo($pdo, $passwordField);
        $quotedName = self::quoteIdentifierWithPdo($pdo, 'name');
        $createdAt = self::quoteIdentifierWithPdo($pdo, 'created_at');
        $updatedAt = self::quoteIdentifierWithPdo($pdo, 'updated_at');

        $columns = [];
        $engine = '';
        $hasCustomName = is_array($extra) && array_key_exists('name', $extra);

        if ($driver === 'mysql') {
            $columns[] = $quotedId . ' BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
            $columns[] = $quotedEmail . ' VARCHAR(191) NOT NULL UNIQUE';
            $columns[] = $quotedPassword . ' VARCHAR(255) NOT NULL';
            if (!$hasCustomName) {
                $columns[] = $quotedName . ' VARCHAR(191) NULL';
            }
            if ($timestamps) {
                $columns[] = $createdAt . ' TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP';
                $columns[] = $updatedAt . ' TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
            }
            $engine = ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        } else {
            $columns[] = $quotedId . ' INTEGER PRIMARY KEY AUTOINCREMENT';
            $columns[] = $quotedEmail . ' TEXT NOT NULL UNIQUE';
            $columns[] = $quotedPassword . ' TEXT NOT NULL';
            if (!$hasCustomName) {
                $columns[] = $quotedName . ' TEXT';
            }
            if ($timestamps) {
                $columns[] = $createdAt . ' DATETIME DEFAULT CURRENT_TIMESTAMP';
                $columns[] = $updatedAt . ' DATETIME DEFAULT CURRENT_TIMESTAMP';
            }
        }

        foreach ($extra as $key => $definition) {
            if (is_int($key)) {
                if (!is_string($definition) || trim($definition) === '') {
                    throw new InvalidArgumentException('AuthManager createUsersTable extra column definitions must be non-empty strings.');
                }
                $columns[] = $definition;
                continue;
            }

            if (!is_string($key) || $key === '') {
                throw new InvalidArgumentException('AuthManager createUsersTable extra column names must be non-empty strings.');
            }
            if (!is_string($definition) || trim($definition) === '') {
                throw new InvalidArgumentException('AuthManager createUsersTable extra column definitions must be non-empty strings.');
            }

            $columns[] = self::quoteIdentifierWithPdo($pdo, $key) . ' ' . $definition;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS ' . $quotedTable
             . ' (' . implode(', ', $columns) . ')' . $engine;

        self::pdoExecute($pdo, $sql);
    }

    protected static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        if (class_exists('DB')
            && method_exists('DB', 'connected')
            && method_exists('DB', 'pdo')
            && DB::connected()
        ) {
            $pdo = DB::pdo();
            self::$pdo = $pdo;
            return $pdo;
        }

        throw new RuntimeException('AuthManager PDO connection is not configured.');
    }

    public static function hashPassword(string $password): string
    {
        return self::hashPasswordInternal($password);
    }

    public static function verify(string $password, string $hash): bool
    {
        return self::verifyPassword($password, $hash);
    }

    protected static function ensureSession(bool $mustBeActive): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        $status = session_status();
        if ($status === PHP_SESSION_DISABLED) {
            throw new RuntimeException('PHP sessions are disabled.');
        }

        if ($mustBeActive && $status !== PHP_SESSION_ACTIVE) {
            if (!self::$autoStartSession) {
                throw new RuntimeException('Session is not active and auto_start_session is disabled.');
            }

            if (!session_start()) {
                throw new RuntimeException('Failed to start session.');
            }
        }
    }

    protected static function hashPasswordInternal(string $password): string
    {
        if (is_callable(self::$passwordHasher)) {
            $hash = call_user_func(self::$passwordHasher, $password);
            if (!is_string($hash) || $hash === '') {
                throw new RuntimeException('Custom password hasher must return a non-empty string.');
            }
            return $hash;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!is_string($hash) || $hash === '') {
            throw new RuntimeException('password_hash() failed to produce a hash.');
        }
        return $hash;
    }

    protected static function verifyPassword(string $password, string $hash): bool
    {
        if (is_callable(self::$passwordVerifier)) {
            return (bool) call_user_func(self::$passwordVerifier, $password, $hash);
        }

        return password_verify($password, $hash);
    }

    protected static function castToArray(mixed $user): array
    {
        if (is_array($user)) {
            return $user;
        }

        if ($user instanceof Traversable) {
            return iterator_to_array($user);
        }

        if (is_object($user)) {
            if (method_exists($user, 'toArray')) {
                $result = $user->toArray();
                if (!is_array($result)) {
                    throw new RuntimeException('User toArray() must return an array.');
                }
                return $result;
            }
            if ($user instanceof JsonSerializable) {
                $result = $user->jsonSerialize();
                if (!is_array($result)) {
                    throw new RuntimeException('User jsonSerialize() must return an array.');
                }
                return $result;
            }
            return get_object_vars($user);
        }

        throw new InvalidArgumentException('User data must be an array or object.');
    }

    protected static function sanitizeUserArray(array $user): array
    {
        if (array_key_exists(self::$passwordField, $user)) {
            unset($user[self::$passwordField]);
        }
        return $user;
    }

    protected static function configureMysqlOption(mixed $config): void
    {
        if ($config === null) {
            $config = [];
        }

        if (!is_array($config)) {
            throw new InvalidArgumentException('AuthManager mysql option must be an array.');
        }

        $table = $config['table'] ?? 'users';
        if (!is_string($table) || trim($table) === '') {
            throw new InvalidArgumentException('AuthManager mysql option "table" must be a non-empty string.');
        }
        $table = trim($table);

        $idField = $config['id_field'] ?? 'id';
        if (!is_string($idField) || trim($idField) === '') {
            throw new InvalidArgumentException('AuthManager mysql option "id_field" must be a non-empty string.');
        }
        $idField = trim($idField);

        $passwordField = $config['password_field'] ?? self::$passwordField;
        if (!is_string($passwordField) || trim($passwordField) === '') {
            throw new InvalidArgumentException('AuthManager mysql option "password_field" must be a non-empty string.');
        }
        $passwordField = trim($passwordField);
        self::$passwordField = $passwordField;

        if (array_key_exists('password_credential_key', $config)) {
            $credentialKey = $config['password_credential_key'];
            if (!is_string($credentialKey) || trim($credentialKey) === '') {
                throw new InvalidArgumentException('AuthManager mysql option "password_credential_key" must be a non-empty string.');
            }
            self::$passwordCredentialKey = trim($credentialKey);
        }

        if (array_key_exists('connection', $config)) {
            $connection = $config['connection'];
            if (!is_array($connection)) {
                throw new InvalidArgumentException('AuthManager mysql connection must be an array.');
            }
            self::configureMysqlConnection($connection);
        }

        $pdo = self::pdo();

        $selectColumns = self::normalizeMysqlSelectColumns($config['columns'] ?? null, $passwordField);
        $selectClause = self::mysqlSelectClause($pdo, $selectColumns);
        $credentialMap = self::normalizeCredentialMap($config['credential_map'] ?? null);
        $fixedConditions = self::normalizeMysqlConditions($config['conditions'] ?? null);
        $refreshAfterPersist = array_key_exists('refresh_after_persist', $config)
            ? (bool) $config['refresh_after_persist']
            : true;

        $quotedTable = self::quoteIdentifierWithPdo($pdo, $table);
        $quotedIdField = self::quoteIdentifierWithPdo($pdo, $idField);

        self::setUserProvider(
            static function (array $credentials) use ($pdo, $table, $quotedTable, $selectClause, $credentialMap, $fixedConditions): ?array {
                $whereParts = [];
                $params = [];
                $index = 0;

                foreach ($credentials as $key => $value) {
                    if (!is_string($key) || $key === '') {
                        continue;
                    }
                    if (is_array($value) || is_object($value)) {
                        continue;
                    }

                    $column = $credentialMap[$key] ?? $key;
                    $param = 'cred_' . $index++;
                    $whereParts[] = self::quoteIdentifierWithPdo($pdo, $column) . ' = :' . $param;
                    $params[$param] = $value;
                }

                foreach ($fixedConditions as $column => $value) {
                    if ($value === null) {
                        $whereParts[] = self::quoteIdentifierWithPdo($pdo, $column) . ' IS NULL';
                    } else {
                        $param = 'cond_' . $index++;
                        $whereParts[] = self::quoteIdentifierWithPdo($pdo, $column) . ' = :' . $param;
                        $params[$param] = $value;
                    }
                }

                if ($whereParts === []) {
                    return null;
                }

                $sql = 'SELECT ' . $selectClause
                     . ' FROM ' . $quotedTable
                     . ' WHERE ' . implode(' AND ', $whereParts)
                     . ' LIMIT 1';

                return self::pdoFetchRow($pdo, $sql, $params);
            }
        );

        self::setUserPersister(
            static function (array $data) use ($pdo, $table, $quotedTable, $idField, $quotedIdField, $selectClause, $refreshAfterPersist, $fixedConditions): array {
                $insertId = self::pdoInsert($pdo, $table, $data);

                $idValue = $data[$idField] ?? null;
                if ($idValue === null || $idValue === '') {
                    if ($insertId !== '' && $insertId !== '0') {
                        $idValue = ctype_digit($insertId)
                            ? (int) $insertId
                            : $insertId;
                    }
                }

                if ($refreshAfterPersist && $idValue !== null && $idValue !== '') {
                    $whereParts = [$quotedIdField . ' = :id'];
                    $params = ['id' => $idValue];
                    $index = 0;

                    foreach ($fixedConditions as $column => $value) {
                        if ($value === null) {
                            $whereParts[] = self::quoteIdentifierWithPdo($pdo, $column) . ' IS NULL';
                        } else {
                            $param = 'cond_' . $index++;
                            $whereParts[] = self::quoteIdentifierWithPdo($pdo, $column) . ' = :' . $param;
                            $params[$param] = $value;
                        }
                    }

                    $sql = 'SELECT ' . $selectClause
                         . ' FROM ' . $quotedTable
                         . ' WHERE ' . implode(' AND ', $whereParts)
                         . ' LIMIT 1';

                    $fresh = self::pdoFetchRow($pdo, $sql, $params);
                    if ($fresh !== null) {
                        return $fresh;
                    }
                }

                if ($idValue !== null && $idValue !== '') {
                    $data[$idField] = $idValue;
                }

                return $data;
            }
        );
    }

    /**
     * @return list<string>|array{0:'*'}
     */
    protected static function normalizeMysqlSelectColumns(mixed $columns, string $passwordField): array
    {
        if ($columns === null) {
            return ['*'];
        }

        if ($columns === '*' || ($columns === ['*'])) {
            return ['*'];
        }

        if (!is_array($columns) || $columns === []) {
            throw new InvalidArgumentException('AuthManager mysql option "columns" must be an array of column names or "*".');
        }

        $normalized = [];
        foreach ($columns as $column) {
            if (!is_string($column) || $column === '') {
                throw new InvalidArgumentException('AuthManager mysql option "columns" must contain non-empty strings.');
            }
            if ($column === '*') {
                return ['*'];
            }
            $normalized[] = $column;
        }

        if (!in_array($passwordField, $normalized, true)) {
            $normalized[] = $passwordField;
        }

        return array_values(array_unique($normalized));
    }

    protected static function mysqlSelectClause(PDO $pdo, array $columns): string
    {
        if ($columns === ['*']) {
            return '*';
        }

        return implode(', ', array_map(
            static fn (string $column): string => self::quoteIdentifierWithPdo($pdo, $column),
            $columns
        ));
    }

    /**
     * @return array<string, string>
     */
    protected static function normalizeCredentialMap(mixed $map): array
    {
        if ($map === null) {
            return [];
        }

        if (!is_array($map)) {
            throw new InvalidArgumentException('AuthManager mysql option "credential_map" must be an array.');
        }

        $normalized = [];
        foreach ($map as $credential => $column) {
            if (!is_string($credential) || $credential === '') {
                throw new InvalidArgumentException('AuthManager mysql option "credential_map" keys must be non-empty strings.');
            }
            if (!is_string($column) || $column === '') {
                throw new InvalidArgumentException('AuthManager mysql option "credential_map" values must be non-empty strings.');
            }
            $normalized[$credential] = $column;
        }

        return $normalized;
    }

    /**
     * @return array<string, scalar|null>
     */
    protected static function normalizeMysqlConditions(mixed $conditions): array
    {
        if ($conditions === null) {
            return [];
        }

        if (!is_array($conditions)) {
            throw new InvalidArgumentException('AuthManager mysql option "conditions" must be an associative array.');
        }

        $normalized = [];
        foreach ($conditions as $column => $value) {
            if (!is_string($column) || $column === '') {
                throw new InvalidArgumentException('AuthManager mysql option "conditions" keys must be non-empty strings.');
            }
            if (is_array($value) || is_object($value)) {
                throw new InvalidArgumentException('AuthManager mysql option "conditions" values must be scalar or null.');
            }
            $normalized[$column] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $connection
     */
    protected static function configureMysqlConnection(array $connection): void
    {
        if (!method_exists('DB', 'mysql')) {
            throw new RuntimeException('AuthManager mysql option requires DB::mysql().');
        }

        $dbname = $connection['dbname'] ?? null;
        if (!is_string($dbname) || $dbname === '') {
            throw new InvalidArgumentException('AuthManager mysql connection requires non-empty "dbname".');
        }

        $username = $connection['username'] ?? null;
        if ($username !== null && !is_string($username)) {
            throw new InvalidArgumentException('AuthManager mysql connection "username" must be string or null.');
        }

        $password = $connection['password'] ?? null;
        if ($password !== null && !is_string($password)) {
            throw new InvalidArgumentException('AuthManager mysql connection "password" must be string or null.');
        }

        $options = [];
        foreach (['host', 'port', 'charset', 'unix_socket', 'attributes'] as $key) {
            if (array_key_exists($key, $connection)) {
                $options[$key] = $connection[$key];
            }
        }

        DB::mysql($dbname, $username, $password, $options);

        if (method_exists('DB', 'pdo')) {
            self::setPdo(DB::pdo());
        }
    }

    protected static function quoteIdentifierWithPdo(PDO $pdo, string $identifier): string
    {
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $parts = explode('.', $identifier);

        $quoted = array_map(
            static function (string $part) use ($driver): string {
                if ($part === '*') {
                    return '*';
                }

                return match ($driver) {
                    'mysql' => '`' . str_replace('`', '``', $part) . '`',
                    default => '"' . str_replace('"', '""', $part) . '"',
                };
            },
            $parts
        );

        return implode('.', $quoted);
    }

    protected static function pdoExecute(PDO $pdo, string $sql, array $params = []): int
    {
        if ($params === []) {
            $result = $pdo->exec($sql);
            if ($result === false) {
                $error = $pdo->errorInfo();
                throw new RuntimeException('PDO exec failed: ' . ($error[2] ?? 'unknown error'));
            }
            return (int) $result;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected static function pdoFetchRow(PDO $pdo, string $sql, array $params): ?array
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    protected static function pdoInsert(PDO $pdo, string $table, array $data): string
    {
        if ($data === []) {
            throw new RuntimeException('Insert requires non-empty data.');
        }

        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        $quotedColumns = array_map(static fn (string $column): string => self::quoteIdentifierWithPdo($pdo, $column), $columns);

        $sql = 'INSERT INTO ' . self::quoteIdentifierWithPdo($pdo, $table)
             . ' (' . implode(', ', $quotedColumns) . ')'
             . ' VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        return $pdo->lastInsertId();
    }

}

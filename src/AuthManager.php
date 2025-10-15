<?php


class AuthManager
{
    protected static ?PDO $pdo = null;
    protected static string $table = 'users';
    protected static string $emailColumn = 'email';
    protected static string $passwordColumn = 'password';
    protected static string $primaryKey = 'id';
    protected static ?array $currentUser = null;

    /**
     * Initialise the manager with a PDO instance and optional configuration.
     *
     * @param array{
     *     table?: string,
     *     email_column?: string,
     *     password_column?: string,
     *     primary_key?: string
     * } $config
     */
    public static function init(PDO $pdo, array $config = []): void
    {
        self::$pdo = $pdo;
        self::$currentUser = null;

        if (isset($config['table'])) {
            self::$table = self::validateIdentifier($config['table'], 'table');
        }

        if (isset($config['email_column'])) {
            self::$emailColumn = self::validateIdentifier($config['email_column'], 'email column');
        }

        if (isset($config['password_column'])) {
            self::$passwordColumn = self::validateIdentifier($config['password_column'], 'password column');
        }

        if (isset($config['primary_key'])) {
            self::$primaryKey = self::validateIdentifier($config['primary_key'], 'primary key');
        }
    }

    /**
     * Create the users table using sensible defaults for the connected driver.
     *
     * @param array{
     *     table?: string,
     *     email_column?: string,
     *     password_column?: string,
     *     primary_key?: string,
     *     extra_columns?: array<string, string>
     * } $options
     */
    public static function createUsersTable(array $options = []): void
    {
        self::ensureInitialized();

        if (isset($options['table'])) {
            self::$table = self::validateIdentifier($options['table'], 'table');
        }

        if (isset($options['email_column'])) {
            self::$emailColumn = self::validateIdentifier($options['email_column'], 'email column');
        }

        if (isset($options['password_column'])) {
            self::$passwordColumn = self::validateIdentifier($options['password_column'], 'password column');
        }

        if (isset($options['primary_key'])) {
            self::$primaryKey = self::validateIdentifier($options['primary_key'], 'primary key');
        }

        $driver = strtolower((string) self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        $columns = self::defaultColumnsForDriver($driver, self::$primaryKey, self::$emailColumn, self::$passwordColumn);

        $extra = $options['extra_columns'] ?? [];
        foreach ($extra as $name => $definition) {
            $name = self::validateIdentifier((string) $name, 'column');
            if (!is_string($definition) || trim($definition) === '') {
                throw new InvalidArgumentException('Extra column definition must be a non-empty string.');
            }
            $columns[] = $name . ' ' . $definition;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS ' . self::$table . " (\n    " . implode(",\n    ", $columns) . "\n)";
        self::$pdo->exec($sql);
    }

    /**
     * Attempt to log in using the provided credentials.
     */
    public static function login(string $email, string $password): ?array
    {
        self::ensureInitialized();

        $user = self::getUserByColumn(self::$emailColumn, $email);
        if ($user === null) {
            return null;
        }

        $hash = $user[self::$passwordColumn] ?? null;
        if (!is_string($hash) || !password_verify($password, $hash)) {
            return null;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            self::updatePasswordHash($user[self::$primaryKey] ?? null, $password);
            $user = self::getUserByColumn(self::$emailColumn, $email) ?? $user;
        }

        $sanitized = self::sanitizeUser($user);
        self::$currentUser = $sanitized;

        return $sanitized;
    }

    /**
     * Register a new user and return the stored record without the password hash.
     *
     * @param array<string, mixed> $attributes
     */
    public static function register(string $email, string $password, array $attributes = []): array
    {
        self::ensureInitialized();

        $email = trim($email);
        if ($email === '') {
            throw new InvalidArgumentException('Email cannot be empty.');
        }

        if ($password === '') {
            throw new InvalidArgumentException('Password cannot be empty.');
        }

        if (self::getUserByColumn(self::$emailColumn, $email) !== null) {
            throw new RuntimeException('A user with that email already exists.');
        }

        $data = $attributes;
        $data[self::$emailColumn] = $email;
        $data[self::$passwordColumn] = password_hash($password, PASSWORD_DEFAULT);

        $columns = array_keys($data);
        foreach ($columns as $column) {
            self::validateIdentifier($column, 'column');
        }

        $placeholders = array_map(static fn ($column) => ':' . $column, $columns);
        $sql = 'INSERT INTO ' . self::$table . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = self::$pdo->prepare($sql);
        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }
        $stmt->execute();

        $user = self::getUserByColumn(self::$emailColumn, $email);
        if ($user === null) {
            throw new RuntimeException('Failed to retrieve newly created user.');
        }

        $sanitized = self::sanitizeUser($user);
        self::$currentUser = $sanitized;

        return $sanitized;
    }

    /**
     * Retrieve the currently authenticated user (if any).
     */
    public static function user(): ?array
    {
        return self::$currentUser;
    }

    /**
     * Ensure that init() was called.
     */
    protected static function ensureInitialized(): void
    {
        if (!self::$pdo instanceof PDO) {
            throw new RuntimeException('AuthManager::init() must be called before using authentication helpers.');
        }
    }

    /**
     * Validate a table/column identifier.
     */
    protected static function validateIdentifier(string $name, string $label): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException('Invalid ' . $label . ' name: ' . $name);
        }

        return $name;
    }

    /**
     * @return array<int, string>
     */
    protected static function defaultColumnsForDriver(string $driver, string $primaryKey, string $emailColumn, string $passwordColumn): array
    {
        switch ($driver) {
            case 'mysql':
                return [
                    $primaryKey . ' INT UNSIGNED AUTO_INCREMENT PRIMARY KEY',
                    $emailColumn . ' VARCHAR(255) NOT NULL UNIQUE',
                    $passwordColumn . ' VARCHAR(255) NOT NULL',
                    'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
                ];

            case 'pgsql':
                return [
                    $primaryKey . ' SERIAL PRIMARY KEY',
                    $emailColumn . ' VARCHAR(255) NOT NULL UNIQUE',
                    $passwordColumn . ' VARCHAR(255) NOT NULL',
                    'created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP'
                ];

            default:
                return [
                    $primaryKey . ' INTEGER PRIMARY KEY AUTOINCREMENT',
                    $emailColumn . ' TEXT NOT NULL UNIQUE',
                    $passwordColumn . ' TEXT NOT NULL',
                    'created_at TEXT DEFAULT CURRENT_TIMESTAMP'
                ];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function getUserByColumn(string $column, string $value): ?array
    {
        self::ensureInitialized();
        self::validateIdentifier($column, 'column');

        $sql = 'SELECT * FROM ' . self::$table . ' WHERE ' . $column . ' = :value LIMIT 1';
        $stmt = self::$pdo->prepare($sql);
        $stmt->bindValue(':value', $value);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user === false) {
            return null;
        }

        return $user;
    }

    protected static function updatePasswordHash($id, string $password): void
    {
        self::ensureInitialized();

        if ($id === null) {
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = 'UPDATE ' . self::$table . ' SET ' . self::$passwordColumn . ' = :hash WHERE ' . self::$primaryKey . ' = :id';
        $stmt = self::$pdo->prepare($sql);
        $stmt->bindValue(':hash', $hash);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    }

    /**
     * Remove the password field from a database record.
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    protected static function sanitizeUser(array $user): array
    {
        unset($user[self::$passwordColumn]);
        return $user;
    }
}

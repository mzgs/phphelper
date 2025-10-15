<?php


class AuthManager
{
    protected static ?PDO $pdo = null;
    protected static string $table = 'users';
    protected static string $emailColumn = 'email';
    protected static string $passwordColumn = 'password';
    protected static string $primaryKey = 'id';
    protected static ?array $currentUser = null;
    protected static bool $useSessions = true;
    protected static string $sessionKey = '_auth_user';
    protected static bool $rememberEnabled = true;
    protected static string $rememberCookie = 'phphelper_remember';
    protected static int $rememberDuration = 31104000; // 360 days
    protected static ?string $rememberSecret = null;
    protected static array $rememberCookieOptions = [
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    /**
     * Initialise the manager with a PDO instance and optional configuration.
     *
     * @param array{
     *     table?: string,
     *     email_column?: string,
     *     password_column?: string,
     *     primary_key?: string,
     *     sessions?: bool,
     *     session_key?: string,
     *     remember_me?: bool,
     *     remember_cookie?: string,
     *     remember_duration?: int,
     *     remember_secret?: string,
     *     remember_options?: array{
     *         path?: string,
     *         domain?: string|null,
     *         secure?: bool,
     *         httponly?: bool,
     *         samesite?: string
     *     }
     * } $config
     */
    public static function init(PDO $pdo, array $config = []): void
    {
        self::$pdo = $pdo;
        self::$currentUser = null;
        self::$useSessions = true;
        self::$sessionKey = '_auth_user';
        self::$rememberEnabled = true;
        self::$rememberCookie = 'phphelper_remember';
        self::$rememberDuration = 31104000;
        self::$rememberSecret = null;
        self::$rememberCookieOptions = [
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

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

        if (isset($config['sessions'])) {
            self::$useSessions = (bool) $config['sessions'];
        }

        if (isset($config['session_key'])) {
            $sessionKey = trim((string) $config['session_key']);
            if ($sessionKey === '') {
                throw new InvalidArgumentException('Session key cannot be empty.');
            }
            self::$sessionKey = $sessionKey;
        }

        if (isset($config['remember_me'])) {
            self::$rememberEnabled = (bool) $config['remember_me'];
        }

        if (isset($config['remember_cookie'])) {
            self::$rememberCookie = self::validateCookieName((string) $config['remember_cookie']);
        }

        if (isset($config['remember_duration'])) {
            $duration = (int) $config['remember_duration'];
            if ($duration <= 0) {
                throw new InvalidArgumentException('Remember duration must be greater than zero.');
            }
            self::$rememberDuration = $duration;
        }

        if (isset($config['remember_secret'])) {
            $secret = trim((string) $config['remember_secret']);
            if ($secret === '') {
                throw new InvalidArgumentException('Remember secret cannot be empty.');
            }
            self::$rememberSecret = $secret;
            if (!isset($config['remember_me'])) {
                self::$rememberEnabled = true;
            }
        }

        if (isset($config['remember_options']) && is_array($config['remember_options'])) {
            $options = array_intersect_key($config['remember_options'], self::$rememberCookieOptions);
            self::$rememberCookieOptions = array_merge(self::$rememberCookieOptions, $options);
        }

        if (self::$rememberEnabled && self::$rememberSecret === null) {
            self::$rememberSecret = self::defaultRememberSecret();
        }

        self::bootstrapSession();
        self::attemptRememberRestore();
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
     *
     * @param bool $remember Issue a persistent signed cookie for future sessions when true.
     */
    public static function login(string $email, string $password, bool $remember = false): ?array
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

        self::regenerateSessionId();

        $sanitized = self::sanitizeUser($user);
        self::persistUser($sanitized, $user, $remember);

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
        self::regenerateSessionId();
        self::persistUser($sanitized, $user, false);

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
     * Determine whether a user is currently authenticated.
     */
    public static function isLoggedIn(): bool
    {
        return self::$currentUser !== null;
    }

    /**
     * Persist the authenticated user details to session/cookie stores.
     *
     * @param array<string, mixed>|null $sanitized
     * @param array<string, mixed>|null $rawUser
     */
    protected static function persistUser(?array $sanitized, ?array $rawUser, bool $remember): void
    {
        if (self::$useSessions && session_status() === PHP_SESSION_NONE) {
            self::bootstrapSession();
        }

        self::$currentUser = $sanitized;

        if (self::$useSessions && session_status() === PHP_SESSION_ACTIVE) {
            if ($sanitized === null) {
                unset($_SESSION[self::$sessionKey]);
            } else {
                $_SESSION[self::$sessionKey] = $sanitized;
            }
        }

        if (!self::$rememberEnabled) {
            return;
        }

        if ($remember && $sanitized !== null && $rawUser !== null) {
            $payload = self::buildRememberPayload($rawUser);
            self::setRememberCookie($payload);
            $_COOKIE[self::$rememberCookie] = $payload;
            return;
        }

        if ($sanitized === null || !$remember) {
            self::clearRememberCookie();
        }
    }

    protected static function bootstrapSession(): void
    {
        if (!self::$useSessions) {
            return;
        }

        if (session_status() === PHP_SESSION_DISABLED) {
            throw new RuntimeException('PHP sessions are disabled.');
        }

        if (session_status() === PHP_SESSION_NONE) {
            if (!session_start()) {
                throw new RuntimeException('Failed to start session.');
            }
        }

        if (self::$currentUser === null) {
            $stored = $_SESSION[self::$sessionKey] ?? null;
            if (is_array($stored)) {
                self::$currentUser = $stored;
            }
        }
    }

    protected static function attemptRememberRestore(): void
    {
        if (!self::$rememberEnabled || self::$currentUser !== null) {
            return;
        }

        $cookieValue = $_COOKIE[self::$rememberCookie] ?? null;
        if (!is_string($cookieValue) || $cookieValue === '') {
            return;
        }

        $decoded = base64_decode($cookieValue, true);
        if ($decoded === false) {
            self::clearRememberCookie();
            return;
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload) || !isset($payload['id'], $payload['sig'])) {
            self::clearRememberCookie();
            return;
        }

        $id = (string) $payload['id'];
        $signature = (string) $payload['sig'];
        if ($id === '' || $signature === '') {
            self::clearRememberCookie();
            return;
        }

        $user = self::getUserByColumn(self::$primaryKey, $id);
        if ($user === null) {
            self::clearRememberCookie();
            return;
        }

        if (!self::validateRememberSignature($user, $signature)) {
            self::clearRememberCookie();
            return;
        }

        $sanitized = self::sanitizeUser($user);
        self::persistUser($sanitized, $user, true);
    }

    /**
     * @param array<string, mixed> $user
     */
    protected static function buildRememberPayload(array $user): string
    {
        $id = $user[self::$primaryKey] ?? null;
        $hash = $user[self::$passwordColumn] ?? null;

        if (!is_scalar($id) || !is_string($hash) || $hash === '') {
            throw new RuntimeException('Unable to create remember-me payload: missing user identifiers.');
        }

        $signature = self::createRememberSignature((string) $id, $hash);
        $json = json_encode([
            'id' => (string) $id,
            'sig' => $signature,
        ]);

        if (!is_string($json)) {
            throw new RuntimeException('Failed to encode remember-me payload.');
        }

        return base64_encode($json);
    }

    /**
     * @param array<string, mixed> $user
     */
    protected static function validateRememberSignature(array $user, string $signature): bool
    {
        $id = $user[self::$primaryKey] ?? null;
        $hash = $user[self::$passwordColumn] ?? null;

        if (!is_scalar($id) || !is_string($hash) || $hash === '') {
            return false;
        }

        $expected = self::createRememberSignature((string) $id, $hash);

        return hash_equals($expected, $signature);
    }

    protected static function createRememberSignature(string $id, string $passwordHash): string
    {
        if (self::$rememberSecret === null) {
            throw new RuntimeException('Remember-me secret is not configured.');
        }

        return hash_hmac('sha256', $id . '|' . $passwordHash, self::$rememberSecret);
    }

    protected static function setRememberCookie(string $value): void
    {
        $options = self::$rememberCookieOptions;
        $options['expires'] = time() + self::$rememberDuration;
        $options['path'] = $options['path'] ?? '/';
        $options['secure'] = (bool) ($options['secure'] ?? false);
        $options['httponly'] = (bool) ($options['httponly'] ?? true);
        if (!isset($options['samesite'])) {
            $options['samesite'] = 'Lax';
        }

        if (!array_key_exists('domain', $options) || $options['domain'] === null || $options['domain'] === '') {
            unset($options['domain']);
        }

        setcookie(self::$rememberCookie, $value, $options);
    }

    protected static function clearRememberCookie(): void
    {
        if (!isset($_COOKIE[self::$rememberCookie])) {
            return;
        }

        $options = self::$rememberCookieOptions;
        $options['expires'] = time() - 3600;
        $options['path'] = $options['path'] ?? '/';
        $options['secure'] = (bool) ($options['secure'] ?? false);
        $options['httponly'] = (bool) ($options['httponly'] ?? true);
        if (!isset($options['samesite'])) {
            $options['samesite'] = 'Lax';
        }

        if (!array_key_exists('domain', $options) || $options['domain'] === null || $options['domain'] === '') {
            unset($options['domain']);
        }

        setcookie(self::$rememberCookie, '', $options);
        unset($_COOKIE[self::$rememberCookie]);
    }

    protected static function regenerateSessionId(): void
    {
        if (!self::$useSessions) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            self::bootstrapSession();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    protected static function validateCookieName(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '' || preg_match('/[=,;\t\r\n\013\014]/', $trimmed)) {
            throw new InvalidArgumentException('Invalid cookie name: ' . $name);
        }

        return $trimmed;
    }

    protected static function defaultRememberSecret(): string
    {
        return hash('sha256', __FILE__ . '|' . php_uname('n'));
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

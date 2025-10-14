<?php

class AuthManager
{
    protected static string $sessionKey = 'auth_user';
    protected static string $passwordField = 'password';
    protected static string $passwordCredentialKey = 'password';
    protected static bool $autoStartSession = true;
    protected static bool $regenerateOnLogin = true;
    protected static bool $regenerateOnLogout = true;

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
        if (array_key_exists('password_hasher', $options)) {
            $verifier = $options['password_verifier'] ?? null;
            self::setPasswordHasher($options['password_hasher'], $verifier);
        } elseif (array_key_exists('password_verifier', $options)) {
            self::setPasswordVerifier($options['password_verifier']);
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

}

<?php

namespace PhpHelper\Auth;

class LoginHelper
{
    private static ?LoginHelper $instance = null;
    private array $config;
    private ?\PhpHelper\Database\DatabaseHelper $db = null;

    private function __construct(array $config = [])
    {
        $this->config = array_merge([
            'table'          => 'users',
            'identity_field' => 'email',
            'password_field' => 'password',
            'session_expire' => 7200,
            'token_expire'   => 86400,
            'hash_algo'      => PASSWORD_ARGON2ID,
            'hash_options'   => [],
        ], $config);
    }

    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function setDatabase(\PhpHelper\Database\DatabaseHelper $db): void
    {
        $this->db = $db;
    }

    public function register(array $userData): array
    {
        if (!isset($userData[$this->config['identity_field']]) || !isset($userData[$this->config['password_field']])) {
            throw new \Exception("Missing required fields");
        }

        $identity = $userData[$this->config['identity_field']];
        if ($this->userExists($identity)) {
            throw new \Exception("User already exists");
        }

        $userData[$this->config['password_field']] = $this->hashPassword($userData[$this->config['password_field']]);

        $userId = $this->db->insert($this->config['table'], $userData);

        return [
            'user_id'  => $userId,
            'identity' => $identity,
        ];
    }

    public function login(string $identity, string $password): array
    {
        $user = $this->db->fetch(
            "SELECT * FROM {$this->config['table']} WHERE {$this->config['identity_field']} = ?",
            [$identity]
        );

        if (!$user || !$this->verifyPassword($password, $user[$this->config['password_field']])) {
            throw new \Exception("Invalid credentials");
        }

        $this->startSession($user);

        return [
            'user_id'  => $user['id'],
            'identity' => $user[$this->config['identity_field']],
            'session'  => session_id(),
        ];
    }

    public function logout(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        return session_destroy();
    }

    public function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user_id']) &&
            isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity']) < $this->config['session_expire'];
    }

    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->db->fetch(
            "SELECT * FROM {$this->config['table']} WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = $this->hashPassword($newPassword);

        return (bool) $this->db->update(
            $this->config['table'],
            [$this->config['password_field'] => $hashedPassword],
            'id = ?',
            [$userId]
        );
    }

    public function generateResetToken(string $identity): string
    {
        $user = $this->db->fetch(
            "SELECT * FROM {$this->config['table']} WHERE {$this->config['identity_field']} = ?",
            [$identity]
        );

        if (!$user) {
            throw new \Exception("User not found");
        }

        $token  = bin2hex(random_bytes(32));
        $expiry = time() + $this->config['token_expire'];

        $this->db->update(
            $this->config['table'],
            [
                'reset_token'  => $token,
                'token_expiry' => date('Y-m-d H:i:s', $expiry),
            ],
            'id = ?',
            [$user['id']]
        );

        return $token;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->db->fetch(
            "SELECT * FROM {$this->config['table']} WHERE reset_token = ? AND token_expiry > NOW()",
            [$token]
        );

        if (!$user) {
            throw new \Exception("Invalid or expired token");
        }

        $success = $this->updatePassword($user['id'], $newPassword);

        if ($success) {
            $this->db->update(
                $this->config['table'],
                ['reset_token' => null, 'token_expiry' => null],
                'id = ?',
                [$user['id']]
            );
        }

        return $success;
    }

    private function userExists(string $identity): bool
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->config['table']} WHERE {$this->config['identity_field']} = ?",
            [$identity]
        );

        return (int) $result['count'] > 0;
    }

    private function hashPassword(string $password): string
    {
        return password_hash($password, $this->config['hash_algo'], $this->config['hash_options']);
    }

    private function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    private function startSession(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['last_activity'] = time();

        setcookie(session_name(), session_id(), [
            'expires'  => time() + $this->config['session_expire'],
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
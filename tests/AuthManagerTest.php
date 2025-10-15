<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/AuthManager.php';

final class AuthManagerTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }

        $_SESSION = [];
        $_COOKIE = [];

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }

        $_SESSION = [];
        $_COOKIE = [];
    }

    private function initManager(array $config = []): void
    {
        $base = [
            'session_key' => 'auth_user_for_tests',
            'remember_secret' => 'test-remember-secret',
        ];
        AuthManager::init($this->pdo, array_merge($base, $config));
        AuthManager::createUsersTable();
    }

    public function testRegisterPersistsUserInSession(): void
    {
        $this->initManager();

        $user = AuthManager::register('session@example.com', 'secret123');

        $this->assertTrue(AuthManager::isLoggedIn());
        $this->assertSame($user, AuthManager::user());
        $this->assertSame($user, $_SESSION['auth_user_for_tests']);
        $this->assertArrayNotHasKey('password', $user);
    }

    public function testLoginWithoutRememberDoesNotIssueCookie(): void
    {
        $this->initManager();

        AuthManager::register('noremember@example.com', 'secret123');

        $_COOKIE = [];
        AuthManager::login('noremember@example.com', 'secret123');

        $this->assertArrayNotHasKey('phphelper_remember', $_COOKIE);
    }

    public function testRememberCookieRestoresAcrossInit(): void
    {
        $config = [
            'remember_secret' => 'unit-test-secret',
            'remember_me' => true,
            'remember_cookie' => 'remember_test_cookie',
        ];

        $this->initManager($config);

        AuthManager::register('remember@example.com', 'secret123');
        AuthManager::login('remember@example.com', 'secret123', true);

        $this->assertArrayHasKey('remember_test_cookie', $_COOKIE);

        $_SESSION = [];
        session_write_close();
        $_SESSION = [];

        AuthManager::init($this->pdo, array_merge(['session_key' => 'auth_user_for_tests'], $config));

        $user = AuthManager::user();
        $this->assertNotNull($user);
        $this->assertSame('remember@example.com', $user['email']);
        $this->assertSame($user, $_SESSION['auth_user_for_tests']);
    }
}

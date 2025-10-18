<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpHelper\DB;
use PhpHelper\AuthManager;

final class AuthManagerTest extends TestCase
{

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }

        $savePath = session_save_path();
        if (!is_string($savePath) || $savePath === '' || !is_dir($savePath) || !is_writable($savePath)) {
            session_save_path(sys_get_temp_dir());
        }

        $_SESSION = [];
        $_COOKIE = [];

        DB::disconnect();
        DB::sqlite(':memory:');
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }

        $_SESSION = [];
        $_COOKIE = [];

        DB::disconnect();
    }

    private function initManager(array $config = []): void
    {
        $base = [
            'session_key' => 'auth_user_for_tests',
            'remember_secret' => 'test-remember-secret',
        ];
        AuthManager::init(array_merge($base, $config));
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

    public function testLogoutClearsSessionAndCurrentUser(): void
    {
        $this->initManager();

        AuthManager::register('logout@example.com', 'secret123');
        $this->assertTrue(AuthManager::isLoggedIn());

        AuthManager::logout();

        $this->assertFalse(AuthManager::isLoggedIn());
        $this->assertNull(AuthManager::user());
        $this->assertArrayNotHasKey('auth_user_for_tests', $_SESSION);
    }

    public function testLogoutClearsRememberCookie(): void
    {
        $this->initManager([
            'remember_cookie' => 'logout_remember_cookie',
            'remember_secret' => 'logout-secret',
        ]);

        AuthManager::register('rememberlogout@example.com', 'secret123');
        AuthManager::login('rememberlogout@example.com', 'secret123', true);

        $this->assertArrayHasKey('logout_remember_cookie', $_COOKIE);

        AuthManager::logout();

        $this->assertArrayNotHasKey('logout_remember_cookie', $_COOKIE);
        $this->assertFalse(AuthManager::isLoggedIn());
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

        AuthManager::init(array_merge(['session_key' => 'auth_user_for_tests'], $config));

        $user = AuthManager::user();
        $this->assertNotNull($user);
        $this->assertSame('remember@example.com', $user['email']);
        $this->assertSame($user, $_SESSION['auth_user_for_tests']);
    }

    public function testRequireAuthReturnsCurrentUser(): void
    {
        $this->initManager();

        $registered = AuthManager::register('required@example.com', 'secret123');

        $this->assertSame($registered, AuthManager::requireAuth());
    }

    public function testRequireAuthThrowsWhenNotAuthenticated(): void
    {
        $this->initManager();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Authentication required.');

        AuthManager::requireAuth();
    }

    public function testRequireAuthInvokesHandlerBeforeThrow(): void
    {
        $this->initManager();

        $invoked = false;

        try {
            AuthManager::requireAuth(function () use (&$invoked): void {
                $invoked = true;
            });
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Authentication required.', $exception->getMessage());
        }

        $this->assertTrue($invoked, 'Unauthenticated handler was not invoked.');
    }
}

<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/AuthManager.php';

final class AuthManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }

        $_SESSION = [];

        AuthManager::configure([
            'session_key' => 'auth_user',
            'password_field' => 'password',
            'password_credential_key' => 'password',
            'auto_start_session' => true,
            'regenerate_on_login' => false,
            'regenerate_on_logout' => false,
            'user_provider' => static fn (): ?array => null,
            'user_persister' => static fn (array $data): array => $data,
        ]);

        AuthManager::setPasswordHasher(
            static fn (string $password): string => password_hash($password, PASSWORD_DEFAULT),
            static fn (string $password, string $hash): bool => password_verify($password, $hash)
        );
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        parent::tearDown();
    }

    public function testAttemptAuthenticatesAndPersistsUser(): void
    {
        $hash = password_hash('secret', PASSWORD_DEFAULT);

        AuthManager::configure([
            'password_field' => 'password_hash',
            'user_provider' => static function (array $credentials) use ($hash): ?array {
                if (($credentials['email'] ?? null) !== 'user@example.com') {
                    return null;
                }
                return [
                    'id' => 10,
                    'email' => 'user@example.com',
                    'password_hash' => $hash,
                    'role' => 'admin',
                ];
            },
        ]);

        $result = AuthManager::attempt([
            'email' => 'user@example.com',
            'password' => 'secret',
        ]);

        $this->assertTrue($result);
        $this->assertTrue(AuthManager::check());

        $user = AuthManager::user();
        $this->assertIsArray($user);
        $this->assertSame(10, AuthManager::id());
        $this->assertSame('user@example.com', $user['email']);
        $this->assertSame('admin', $user['role']);
        $this->assertArrayNotHasKey('password_hash', $user);
    }

    public function testAttemptFailsWithInvalidCredentials(): void
    {
        $hash = password_hash('secret', PASSWORD_DEFAULT);

        AuthManager::configure([
            'user_provider' => static function (array $credentials) use ($hash): ?array {
                if (($credentials['username'] ?? null) !== 'tester') {
                    return null;
                }
                return [
                    'id' => 55,
                    'username' => 'tester',
                    'password' => $hash,
                ];
            },
        ]);

        $this->assertFalse(AuthManager::attempt([
            'username' => 'tester',
            'password' => 'wrong',
        ]));
        $this->assertFalse(AuthManager::check());
        $this->assertNull(AuthManager::user());
    }

    public function testRegisterHashesPasswordAndCanAutoLogin(): void
    {
        $stored = null;

        AuthManager::setUserPersister(static function (array $data) use (&$stored): array {
            $stored = $data;
            $data['id'] = 99;
            return $data;
        });

        $user = AuthManager::register([
            'email' => 'register@example.com',
            'password' => 'topsecret',
        ], true);

        $this->assertIsArray($user);
        $this->assertSame(99, $user['id']);
        $this->assertSame('register@example.com', $user['email']);
        $this->assertArrayNotHasKey('password', $user);
        $this->assertTrue(AuthManager::check());
        $this->assertTrue(AuthManager::verify('topsecret', $stored['password'] ?? ''));
        $this->assertNotSame('topsecret', $stored['password'] ?? '');
    }

    public function testUpdateUserMergesDataAndSanitizesPasswordField(): void
    {
        AuthManager::login([
            'id' => 1,
            'name' => 'Alice',
            'password' => 'should-strip',
        ]);

        AuthManager::updateUser([
            'name' => 'Bob',
            'password' => 'new-should-strip',
        ]);

        $user = AuthManager::user();
        $this->assertSame([
            'id' => 1,
            'name' => 'Bob',
        ], $user);
    }

    public function testCustomHasherAndVerifier(): void
    {
        AuthManager::setPasswordHasher(
            static fn (string $password): string => 'custom:' . strtoupper($password),
            static fn (string $password, string $hash): bool => $hash === 'custom:' . strtoupper($password)
        );

        $hash = AuthManager::hashPassword('secret');
        $this->assertSame('custom:SECRET', $hash);
        $this->assertTrue(AuthManager::verify('secret', $hash));
        $this->assertFalse(AuthManager::verify('other', $hash));
    }
}

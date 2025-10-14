<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/AuthManager.php';
require_once __DIR__ . '/../src/DB.php';

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

        AuthManager::setPdo(null);

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

    public function testMysqlOptionUsesDatabaseProviderAndPersister(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite not available');
        }

        DB::connect('sqlite::memory:');

        try {
            DB::execute('CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                name TEXT NOT NULL,
                active INTEGER NOT NULL DEFAULT 1
            )');

            $activeHash = password_hash('secret', PASSWORD_DEFAULT);
            $inactiveHash = password_hash('hidden', PASSWORD_DEFAULT);

            DB::insert('users', [
                'email' => 'jane@example.com',
                'password_hash' => $activeHash,
                'name' => 'Jane',
                'active' => 1,
            ]);

            DB::insert('users', [
                'email' => 'inactive@example.com',
                'password_hash' => $inactiveHash,
                'name' => 'Inactive',
                'active' => 0,
            ]);

            AuthManager::configure([
                'mysql' => [
                    'table' => 'users',
                    'password_field' => 'password_hash',
                    'columns' => ['id', 'email', 'password_hash', 'name', 'active'],
                    'credential_map' => ['username' => 'email'],
                    'conditions' => ['active' => 1],
                ],
            ]);

            $this->assertTrue(AuthManager::attempt([
                'username' => 'jane@example.com',
                'password' => 'secret',
            ]));

            $user = AuthManager::user();
            $this->assertIsArray($user);
            $this->assertSame('jane@example.com', $user['email']);
            $this->assertSame('Jane', $user['name']);
            $this->assertSame(1, $user['active']);
            $this->assertArrayNotHasKey('password_hash', $user);

            AuthManager::logout();

            $this->assertFalse(AuthManager::attempt([
                'username' => 'inactive@example.com',
                'password' => 'hidden',
            ]));

            $registered = AuthManager::register([
                'email' => 'new@example.com',
                'password_hash' => 'topsecret',
                'name' => 'New User',
                'active' => 1,
            ], true);

            $this->assertIsArray($registered);
            $this->assertArrayHasKey('id', $registered);
            $this->assertSame('new@example.com', $registered['email']);
            $this->assertSame('New User', $registered['name']);
            $this->assertTrue(AuthManager::check());

            $stored = DB::getRow('SELECT * FROM users WHERE email = :email', ['email' => 'new@example.com']);
            $this->assertNotNull($stored);
            $this->assertTrue(AuthManager::verify('topsecret', $stored['password_hash'] ?? ''));

            AuthManager::logout();
        } finally {
            if (DB::connected()) {
                DB::disconnect();
            }
            AuthManager::setPdo(null);
        }
    }

    public function testMysqlOptionDefaultsAllowMinimalConfiguration(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite not available');
        }

        DB::connect('sqlite::memory:');

        try {
            AuthManager::configure([
                'mysql' => [
                    'pdo' => DB::pdo(),
                ],
            ]);

            AuthManager::createUsersTable();

            $registered = AuthManager::register([
                'email' => 'minimal@example.com',
                'password' => 'secret123',
            ], true);

            $this->assertTrue(AuthManager::check());
            $this->assertSame('minimal@example.com', $registered['email']);
            $this->assertArrayNotHasKey('password', $registered);

            AuthManager::logout();

            $this->assertTrue(AuthManager::attempt([
                'email' => 'minimal@example.com',
                'password' => 'secret123',
            ]));

            $user = AuthManager::user();
            $this->assertIsArray($user);
            $this->assertSame('minimal@example.com', $user['email']);
        } finally {
            if (DB::connected()) {
                DB::disconnect();
            }
            AuthManager::setPdo(null);
        }
    }

    public function testCreateUsersTableCreatesExpectedSchema(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite not available');
        }

        DB::connect('sqlite::memory:');

        try {
            AuthManager::createUsersTable([
                'table' => 'auth_users',
                'password_field' => 'password_hash',
                'extra_columns' => [
                    'status INTEGER NOT NULL DEFAULT 1',
                ],
            ]);

            $info = DB::getRows('PRAGMA table_info(auth_users)');
            $this->assertNotEmpty($info);

            $names = array_map(static fn (array $col): string => (string) $col['name'], $info);
            $this->assertContains('id', $names);
            $this->assertContains('email', $names);
            $this->assertContains('password_hash', $names);
            $this->assertContains('name', $names);
            $this->assertContains('status', $names);
            $this->assertContains('created_at', $names);
            $this->assertContains('updated_at', $names);

            $idMeta = null;
            foreach ($info as $column) {
                if (($column['name'] ?? null) === 'id') {
                    $idMeta = $column;
                    break;
                }
            }

            $this->assertNotNull($idMeta);
            $this->assertSame(1, (int) ($idMeta['pk'] ?? 0));

            DB::insert('auth_users', [
                'email' => 'unique@example.com',
                'password_hash' => 'hash-one',
                'name' => 'Unique',
                'status' => 1,
            ]);

            try {
                DB::insert('auth_users', [
                    'email' => 'unique@example.com',
                    'password_hash' => 'hash-two',
                    'name' => 'Duplicate',
                    'status' => 1,
                ]);
                $this->fail('Expected unique constraint violation for email column.');
            } catch (\PDOException $e) {
                $this->assertStringContainsStringIgnoringCase('unique', (string) $e->getMessage());
            }
        } finally {
            if (DB::connected()) {
                DB::disconnect();
            }
            AuthManager::setPdo(null);
        }
    }
}

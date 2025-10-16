<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Logs.php';

final class LogsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite not available');
        }

        DB::connect('sqlite::memory:');
        Logs::setTable('logs');
        Logs::createLogsTable();
        Logs::clearDefaults();
    }

    protected function tearDown(): void
    {
        if (DB::connected()) {
            DB::disconnect();
        }

        Logs::setTable('logs');
        Logs::clearDefaults();

        parent::tearDown();
    }

    public function testContextAndMetaDefaultsAreApplied(): void
    {
        Logs::setContextDefaults([
            'application' => 'TestApp',
            'user_id' => 100,
        ]);

        Logs::setMetaDefaults([
            'request_id' => 'req-default',
            'ip' => '0.0.0.0',
        ]);

        $id = Logs::info('Defaults example', ['user_id' => 200], ['ip' => '127.0.0.1']);

        $row = DB::getRow('SELECT context, meta FROM logs WHERE id = :id', ['id' => $id]);
        $this->assertNotNull($row);

        $context = json_decode((string) $row['context'], true);
        $this->assertIsArray($context);
        $this->assertSame([
            'application' => 'TestApp',
            'user_id' => 200,
        ], $context);

        $meta = json_decode((string) $row['meta'], true);
        $this->assertIsArray($meta);
        $this->assertSame([
            'request_id' => 'req-default',
            'ip' => '127.0.0.1',
        ], $meta);
    }

    public function testLogInsertsRowWithJsonContextAndMeta(): void
    {
        $id = Logs::log('INFO', 'User login', ['user_id' => 42], ['ip' => '127.0.0.1']);

        $this->assertSame('1', $id);

        $row = DB::getRow('SELECT level, message, context, meta FROM logs WHERE id = :id', ['id' => $id]);
        $this->assertNotNull($row);
        $this->assertSame('info', $row['level']);
        $this->assertSame('User login', $row['message']);

        $context = json_decode((string) $row['context'], true);
        $this->assertIsArray($context);
        $this->assertSame(['user_id' => 42], $context);

        $meta = json_decode((string) $row['meta'], true);
        $this->assertIsArray($meta);
        $this->assertSame(['ip' => '127.0.0.1'], $meta);
    }

    public function testSuccessConvenienceMethodPersistsRecord(): void
    {
        $id = Logs::success('Action completed', ['user_id' => 8], ['job_id' => 'abc']);

        $this->assertSame('1', $id);

        $row = DB::getRow('SELECT level, message, context, meta FROM logs WHERE id = :id', ['id' => $id]);
        $this->assertNotNull($row);
        $this->assertSame('success', $row['level']);
        $this->assertSame('Action completed', $row['message']);

        $context = json_decode((string) $row['context'], true);
        $this->assertIsArray($context);
        $this->assertSame(['user_id' => 8], $context);

        $meta = json_decode((string) $row['meta'], true);
        $this->assertIsArray($meta);
        $this->assertSame(['job_id' => 'abc'], $meta);
    }

    public function testEnsureTableCreatesStructureOnSqlite(): void
    {
        DB::execute('DROP TABLE logs');

        Logs::createLogsTable('logs');

        $table = DB::getRow(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name",
            ['name' => 'logs']
        );

        $this->assertNotNull($table, 'Expected logs table to exist after createLogsTable call.');
    }

    public function testJsonEncodingFailureBecomesInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to encode value to JSON');

        $resource = fopen('php://memory', 'rb');
        try {
            Logs::log('error', 'Broken context', ['handle' => $resource]);
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }
}

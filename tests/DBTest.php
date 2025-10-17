<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpHelper\DB;

final class DBTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite not available');
        }

        DB::connect('sqlite::memory:');
        DB::execute('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            age INTEGER NOT NULL
        )');
    }

    protected function tearDown(): void
    {
        if (DB::connected()) {
            DB::disconnect();
        }
        parent::tearDown();
    }

    public function testConnectAndBasicCrud(): void
    {
        $this->assertTrue(DB::connected());

        $id1 = DB::insert('users', ['name' => 'Ada', 'age' => 36]);
        $id2 = DB::insert('users', ['name' => 'Grace', 'age' => 45]);

        $this->assertNotSame('', $id1);
        $this->assertNotSame('', $id2);

        $row = DB::getRow('SELECT * FROM users WHERE id = :id', ['id' => $id1]);
        $this->assertNotNull($row);
        $this->assertSame('Ada', $row['name']);
        $this->assertSame(36, (int) $row['age']);

        $all = DB::getRows('SELECT name FROM users ORDER BY id ASC');
        $this->assertSame([
            ['name' => 'Ada'],
            ['name' => 'Grace'],
        ], $all);

        $updated = DB::update('users', ['age' => 37], 'name = :n', ['n' => 'Ada']);
        $this->assertSame(1, $updated);

        $count = DB::count('users', 'age >= ?', [40]);
        $this->assertSame(1, $count);

        $deleted = DB::delete('users', 'name = :n', ['n' => 'Grace']);
        $this->assertSame(1, $deleted);

        $remaining = DB::count('users');
        $this->assertSame(1, $remaining);
    }

    public function testTransactionCommitAndRollback(): void
    {
        $start = DB::count('users');

        DB::transaction(function (): void {
            DB::insert('users', ['name' => 'Linus', 'age' => 30]);
            DB::insert('users', ['name' => 'Margaret', 'age' => 28]);
        });

        $afterCommit = DB::count('users');
        $this->assertSame($start + 2, $afterCommit);

        try {
            DB::transaction(function (): void {
                DB::insert('users', ['name' => 'Error', 'age' => 99]);
                throw new RuntimeException('fail');
            });
            $this->fail('Expected exception not thrown');
        } catch (RuntimeException $e) {
            $this->assertSame('fail', $e->getMessage());
        }

        $afterRollback = DB::count('users');
        $this->assertSame($afterCommit, $afterRollback);
    }

    

    public function testQuoteIdentifierProducesDriverSpecificQuotes(): void
    {
        // SQLite uses double quotes
        $this->assertSame('"users"', DB::quoteIdentifier('users'));
        $this->assertSame('"u"."name"', DB::quoteIdentifier('u.name'));
        $this->assertSame('*', DB::quoteIdentifier('*'));
    }

    public function testUpsertInsertsAndUpdates(): void
    {
        DB::upsert('users', [
            'id' => 1,
            'name' => 'Initial',
            'age' => 20,
        ], 'id');

        $row = DB::getRow('SELECT name, age FROM users WHERE id = 1');
        $this->assertSame('Initial', $row['name'] ?? null);
        $this->assertSame(20, (int) ($row['age'] ?? 0));

        DB::upsert('users', [
            'id' => 1,
            'name' => 'Initial',
            'age' => 25,
        ], 'id', ['age']);

        $row = DB::getRow('SELECT name, age FROM users WHERE id = 1');
        $this->assertSame(25, (int) ($row['age'] ?? 0));

        DB::upsert('users', [
            'id' => 1,
            'name' => 'Updated',
            'age' => 25,
        ], 'id');

        $row = DB::getRow('SELECT name, age FROM users WHERE id = 1');
        $this->assertSame('Updated', $row['name'] ?? null);
    }
}

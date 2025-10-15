<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Config.php';

final class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite not available');
        }

        DB::connect('sqlite::memory:');
        Config::init();
        Config::createConfigTable();
    }

    protected function tearDown(): void
    {
        if (DB::connected()) {
            DB::disconnect();
        }

        parent::tearDown();
    }

    public function testSetAndGetValue(): void
    {
        Config::set('site_name', 'My Site');
        Config::set('site_name', 'My Site');

        $this->assertTrue(Config::has('site_name'));
        $this->assertSame('My Site', Config::get('site_name'));
        $this->assertSame('fallback', Config::get('missing', 'fallback'));
        $this->assertSame(['site_name' => 'My Site'], Config::all());
        $this->assertSame(1, DB::count('config'));
    }

    public function testDeleteRemovesKey(): void
    {
        Config::set('timezone', 'UTC');

        $this->assertTrue(Config::delete('timezone'));
        $this->assertFalse(Config::has('timezone'));
        $this->assertNull(Config::get('timezone'));
    }

    public function testCustomTableAndColumns(): void
    {
        Config::init([
            'table' => 'settings',
            'key_column' => 'name',
            'value_column' => 'val_text',
        ]);

        Config::createConfigTable();
        Config::set('language', 'en');

        $this->assertSame('en', Config::get('language'));

        $columns = DB::getRows('PRAGMA table_info(settings)');
        $types = [];
        $notNull = [];
        foreach ($columns as $column) {
            $name = $column['name'] ?? null;
            $type = $column['type'] ?? null;
            $notNullFlag = $column['notnull'] ?? null;
            if (is_string($name)) {
                $types[$name] = strtoupper((string) $type);
                $notNull[$name] = (int) $notNullFlag;
            }
        }

        $this->assertSame('TEXT', $types['val_text'] ?? null);
        $this->assertSame(0, $notNull['val_text'] ?? null);
        $this->assertSame('TEXT', $types['modified_at'] ?? null);
    }

    public function testRepeatedSetUpdatesExistingRow(): void
    {
        Config::set('feature_flag', 'off');
        Config::set('feature_flag', 'off');
        Config::set('feature_flag', 'on');

        $this->assertSame('on', Config::get('feature_flag'));
        $this->assertSame(1, DB::count('config'));
    }

    public function testSetAllowsNullValues(): void
    {
        Config::set('optional', null);

        $this->assertTrue(Config::has('optional'));
        $this->assertNull(Config::get('optional'));

        $all = Config::all();
        $this->assertArrayHasKey('optional', $all);
        $this->assertNull($all['optional']);
    }

    public function testModifiedTimestampUpdatesOnChange(): void
    {
        Config::set('timezone', 'UTC');
        $first = DB::getValue(
            'SELECT modified_at FROM config WHERE config_key = :key',
            ['key' => 'timezone']
        );
        $this->assertNotNull($first);

        usleep(1_100_000); // Ensure next timestamp differs at second precision.

        Config::set('timezone', 'BST');
        $second = DB::getValue(
            'SELECT modified_at FROM config WHERE config_key = :key',
            ['key' => 'timezone']
        );

        $this->assertNotNull($second);
        $this->assertNotSame($first, $second);
    }
}

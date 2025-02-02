<?php

namespace Tests\Utils;

use PHPUnit\Framework\TestCase;
use PhpHelper\Utils\ArrayHelper;

class ArrayHelperTest extends TestCase
{
    private array $testArray;

    protected function setUp(): void
    {
        $this->testArray = [
            'name'     => 'John Doe',
            'contacts' => [
                'email' => 'john@example.com',
                'phone' => '1234567890',
            ],
            'profile'  => [
                'address' => [
                    'city'    => 'New York',
                    'country' => 'USA',
                ],
            ],
        ];
    }

    public function testGet(): void
    {
        $this->assertEquals('John Doe', ArrayHelper::get($this->testArray, 'name'));
        $this->assertEquals('john@example.com', ArrayHelper::get($this->testArray, 'contacts.email'));
        $this->assertEquals('New York', ArrayHelper::get($this->testArray, 'profile.address.city'));
        $this->assertEquals('default', ArrayHelper::get($this->testArray, 'invalid.key', 'default'));
    }

    public function testSet(): void
    {
        $array = [];
        ArrayHelper::set($array, 'user.name', 'Jane Doe');
        ArrayHelper::set($array, 'user.email', 'jane@example.com');

        $this->assertEquals([
            'user' => [
                'name'  => 'Jane Doe',
                'email' => 'jane@example.com',
            ],
        ], $array);
    }

    public function testHas(): void
    {
        $this->assertTrue(ArrayHelper::has($this->testArray, 'name'));
        $this->assertTrue(ArrayHelper::has($this->testArray, 'contacts.email'));
        $this->assertFalse(ArrayHelper::has($this->testArray, 'invalid.key'));
    }

    public function testRemove(): void
    {
        $array = $this->testArray;
        ArrayHelper::remove($array, 'contacts.email');

        $this->assertFalse(ArrayHelper::has($array, 'contacts.email'));
        $this->assertTrue(ArrayHelper::has($array, 'contacts.phone'));
    }

    public function testOnly(): void
    {
        $array  = ['name' => 'John', 'email' => 'john@example.com', 'age' => 30];
        $result = ArrayHelper::only($array, ['name', 'email']);

        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $result);
    }

    public function testExcept(): void
    {
        $array  = ['name' => 'John', 'email' => 'john@example.com', 'age' => 30];
        $result = ArrayHelper::except($array, ['age']);

        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $result);
    }

    public function testPluck(): void
    {
        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $names = ArrayHelper::pluck($array, 'name');
        $this->assertEquals(['John', 'Jane'], $names);

        $namesById = ArrayHelper::pluck($array, 'name', 'id');
        $this->assertEquals([1 => 'John', 2 => 'Jane'], $namesById);

        // Test with dot notation
        $nested = [
            ['user' => ['profile' => ['name' => 'John', 'id' => 1]]],
            ['user' => ['profile' => ['name' => 'Jane', 'id' => 2]]],
        ];
        $names  = ArrayHelper::pluck($nested, 'user.profile.name');
        $this->assertEquals(['John', 'Jane'], $names);

        $byProfileId = ArrayHelper::pluck($nested, 'user.profile.name', 'user.profile.id');
        $this->assertEquals([1 => 'John', 2 => 'Jane'], $byProfileId);
    }

    public function testGroupBy(): void
    {
        $array = [
            ['category' => 'A', 'name' => 'Item 1'],
            ['category' => 'B', 'name' => 'Item 2'],
            ['category' => 'A', 'name' => 'Item 3'],
        ];

        $result = ArrayHelper::groupBy($array, 'category');

        $this->assertCount(2, $result);
        $this->assertCount(2, $result['A']);
        $this->assertCount(1, $result['B']);

        // Test with dot notation
        $nested = [
            ['user' => ['profile' => ['type' => 'premium', 'name' => 'John']]],
            ['user' => ['profile' => ['type' => 'basic', 'name' => 'Jane']]],
            ['user' => ['profile' => ['type' => 'premium', 'name' => 'Bob']]],
        ];

        $result = ArrayHelper::groupBy($nested, 'user.profile.type');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['premium']);
        $this->assertCount(1, $result['basic']);
    }

    public function testWhere(): void
    {
        $array = [
            ['id' => 1, 'active' => true],
            ['id' => 2, 'active' => false],
            ['id' => 3, 'active' => true],
        ];

        $result = ArrayHelper::where($array, 'active', true);
        $this->assertCount(2, $result);

        $result = ArrayHelper::where($array, 'id', 2, '>');
        $this->assertCount(1, $result);

        // Test with dot notation
        $nestedArray = [
            ['user' => ['profile' => ['age' => 25]]],
            ['user' => ['profile' => ['age' => 30]]],
            ['user' => ['profile' => ['age' => 20]]],
        ];

        $result = ArrayHelper::where($nestedArray, 'user.profile.age', 25, '>=');
        $this->assertCount(2, $result);
    }

    public function testFlatten(): void
    {
        $array = [1, [2, 3, [4, 5]], 6];

        // Test complete flattening
        $this->assertEquals(
            [1, 2, 3, 4, 5, 6],
            array_values(ArrayHelper::flatten($array, PHP_INT_MAX))
        );

        // Test single level flattening
        $result = ArrayHelper::flatten($array, 1);
        $this->assertCount(5, $result);
        $this->assertEquals(1, $result[0]);
        $this->assertEquals(2, $result[1]);
        $this->assertEquals(3, $result[2]);
        $this->assertEquals([4, 5], $result[3]);
        $this->assertEquals(6, $result[4]);
    }

    public function testUnique(): void
    {
        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
            ['id' => 3, 'name' => 'John'],
        ];

        $result = ArrayHelper::unique($array, 'name');
        $this->assertCount(2, $result);

        // Test with dot notation
        $nested = [
            ['user' => ['profile' => ['role' => 'admin']]],
            ['user' => ['profile' => ['role' => 'user']]],
            ['user' => ['profile' => ['role' => 'admin']]],
        ];

        $result = ArrayHelper::unique($nested, 'user.profile.role');
        $this->assertCount(2, $result);
    }

    public function testKeyBy(): void
    {
        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $result = ArrayHelper::keyBy($array, 'id');
        $this->assertEquals([
            1 => ['id' => 1, 'name' => 'John'],
            2 => ['id' => 2, 'name' => 'Jane'],
        ], $result);

        // Test with dot notation
        $nested = [
            ['user' => ['profile' => ['uuid' => 'abc', 'name' => 'John']]],
            ['user' => ['profile' => ['uuid' => 'xyz', 'name' => 'Jane']]],
        ];

        $result = ArrayHelper::keyBy($nested, 'user.profile.uuid');
        $this->assertEquals([
            'abc' => ['user' => ['profile' => ['uuid' => 'abc', 'name' => 'John']]],
            'xyz' => ['user' => ['profile' => ['uuid' => 'xyz', 'name' => 'Jane']]],
        ], $result);
    }

    public function testRandom(): void
    {
        $array = [1, 2, 3, 4, 5];

        $single = ArrayHelper::random($array);
        $this->assertContains($single, $array);

        $multiple = ArrayHelper::random($array, 3);
        $this->assertCount(3, $multiple);
    }

    public function testShuffle(): void
    {
        $array    = [1, 2, 3, 4, 5];
        $shuffled = ArrayHelper::shuffle($array);

        $this->assertCount(count($array), $shuffled);
        $this->assertEquals([], array_diff($array, $shuffled));
    }

    public function testFirst(): void
    {
        $array = [1, 2, 3, 4, 5];
        $this->assertEquals(1, ArrayHelper::first($array));
        $this->assertNull(ArrayHelper::first([]));
        $this->assertEquals('default', ArrayHelper::first([], 'default'));
    }

    public function testLast(): void
    {
        $array = [1, 2, 3, 4, 5];
        $this->assertEquals(5, ArrayHelper::last($array));
        $this->assertNull(ArrayHelper::last([]));
        $this->assertEquals('default', ArrayHelper::last([], 'default'));
    }

    public function testWhereFirst(): void
    {
        $array = [
            ['id' => 1, 'active' => true],
            ['id' => 2, 'active' => false],
            ['id' => 3, 'active' => true],
        ];

        $result = ArrayHelper::whereFirst($array, 'active', true);
        $this->assertEquals(['id' => 1, 'active' => true], $result);

        $result = ArrayHelper::whereFirst($array, 'id', 2, '>');
        $this->assertEquals(['id' => 3, 'active' => true], $result);

        $this->assertNull(ArrayHelper::whereFirst($array, 'id', 5, '>'));

        // Test with dot notation
        $nestedArray = [
            ['user' => ['profile' => ['active' => true]]],
            ['user' => ['profile' => ['active' => false]]],
            ['user' => ['profile' => ['active' => true]]],
        ];

        $result = ArrayHelper::whereFirst($nestedArray, 'user.profile.active', true);
        $this->assertEquals(['user' => ['profile' => ['active' => true]]], $result);
    }

    public function testWhereLast(): void
    {
        $array = [
            ['id' => 1, 'active' => true],
            ['id' => 2, 'active' => false],
            ['id' => 3, 'active' => true],
        ];

        $result = ArrayHelper::whereLast($array, 'active', true);
        $this->assertEquals(['id' => 3, 'active' => true], $result);

        $result = ArrayHelper::whereLast($array, 'id', 2, '<');
        $this->assertEquals(['id' => 1, 'active' => true], $result);

        $this->assertNull(ArrayHelper::whereLast($array, 'id', 5, '>'));

        // Test with dot notation
        $nestedArray = [
            ['user' => ['profile' => ['role' => 'admin']]],
            ['user' => ['profile' => ['role' => 'user']]],
            ['user' => ['profile' => ['role' => 'admin']]],
        ];

        $result = ArrayHelper::whereLast($nestedArray, 'user.profile.role', 'admin');
        $this->assertEquals(['user' => ['profile' => ['role' => 'admin']]], $result);
    }
}
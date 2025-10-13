<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Arrays.php';

final class ArraysTest extends TestCase
{
    public function testGetReturnsNestedValueAndFallsBackToDefault(): void
    {
        $data = [
            'plain' => 'value',
            'user' => [
                'profile' => [
                    'name' => 'Ada',
                ],
            ],
        ];

        $this->assertSame('value', Arrays::get($data, 'plain'));
        $this->assertSame('Ada', Arrays::get($data, 'user.profile.name'));
        $this->assertNull(Arrays::get($data, 'user.address'));
        $this->assertSame('fallback', Arrays::get($data, 'user.address.city', 'fallback'));
    }

    public function testSetCreatesIntermediateArrays(): void
    {
        $data = [];

        Arrays::set($data, 'settings.theme.color', 'blue');
        Arrays::set($data, 'settings.language', 'en');

        $this->assertSame([
            'settings' => [
                'theme' => ['color' => 'blue'],
                'language' => 'en',
            ],
        ], $data);
    }

    public function testHasDetectsDotPaths(): void
    {
        $data = ['config' => ['debug' => true]];

        $this->assertTrue(Arrays::has($data, 'config.debug'));
        $this->assertFalse(Arrays::has($data, 'config.cache'));
        $this->assertFalse(Arrays::has($data, 'config.debug.enabled'));
    }

    public function testForgetRemovesNestedKeysGracefully(): void
    {
        $data = [
            'user' => [
                'profile' => [
                    'email' => 'user@example.com',
                    'name' => 'User',
                ],
            ],
        ];

        Arrays::forget($data, 'user.profile.email');
        Arrays::forget($data, 'user.preferences.theme');

        $this->assertSame([
            'user' => [
                'profile' => ['name' => 'User'],
            ],
        ], $data);
    }

    public function testFlattenRespectsDepth(): void
    {
        $nested = [1, [2, [3, 4], 5], 6];

        $this->assertSame([1, 2, 3, 4, 5, 6], Arrays::flatten($nested, PHP_INT_MAX));
        $this->assertSame([1, 2, [3, 4], 5, 6], Arrays::flatten($nested, 1));
    }

    public function testDotFlattensWithOptionalPrefix(): void
    {
        $array = [
            'user' => [
                'profile' => ['name' => 'Ada'],
                'roles' => [],
            ],
        ];

        $this->assertSame([
            'user.profile.name' => 'Ada',
            'user.roles' => [],
        ], Arrays::dot($array));

        $this->assertSame([
            'meta.user.profile.name' => 'Ada',
            'meta.user.roles' => [],
        ], Arrays::dot($array, 'meta.'));
    }

    public function testOnlyAndExceptSelectKeys(): void
    {
        $data = ['id' => 1, 'name' => 'Ada', 'language' => 'PHP'];

        $this->assertSame(['id' => 1, 'name' => 'Ada'], Arrays::only($data, ['id', 'name']));
        $this->assertSame(['language' => 'PHP'], Arrays::except($data, ['id', 'name']));
    }

    public function testFirstAndLastWithCallbacksAndDefaults(): void
    {
        $numbers = [1, 2, 3, 4];
        $empty = [];

        $this->assertSame(1, Arrays::first($numbers));
        $this->assertSame(2, Arrays::first($numbers, fn ($value) => $value % 2 === 0));
        $this->assertSame('default', Arrays::first($empty, null, 'default'));

        $this->assertSame(4, Arrays::last($numbers));
        $this->assertSame(4, Arrays::last($numbers, fn ($value) => $value % 2 === 0));
        $this->assertSame('fallback', Arrays::last($empty, null, 'fallback'));
    }

    public function testWhereFamilyFiltersByNestedValues(): void
    {
        $records = [
            ['id' => 1, 'type' => 'a', 'active' => true],
            ['id' => 2, 'type' => 'b', 'active' => false],
            ['id' => 3, 'type' => 'a', 'active' => true],
        ];

        $onlyActive = Arrays::where($records, fn ($item) => $item['active']);
        $typeA = Arrays::whereEquals($records, 'type', 'a');
        $idsIn = Arrays::whereIn($records, 'id', [1, 3]);
        $idsNotIn = Arrays::whereNotIn($records, 'id', [2]);

        $this->assertCount(2, $onlyActive);
        $this->assertCount(2, $typeA);
        $this->assertCount(2, $idsIn);
        $this->assertCount(2, $idsNotIn);
    }

    public function testPluckExtractsValuesWithOptionalKeys(): void
    {
        $users = [
            ['id' => 1, 'profile' => ['username' => 'ada']],
            ['id' => 2, 'profile' => ['username' => 'grace']],
        ];

        $this->assertSame(['ada', 'grace'], Arrays::pluck($users, 'profile.username'));
        $this->assertSame([
            1 => 'ada',
            2 => 'grace',
        ], Arrays::pluck($users, 'profile.username', 'id'));
    }

    public function testKeyByAndGroupBySupportClosuresAndDotNotation(): void
    {
        $users = [
            ['id' => 1, 'profile' => ['username' => 'ada'], 'team' => 'core'],
            ['id' => 2, 'profile' => ['username' => 'grace'], 'team' => 'core'],
            ['id' => 3, 'profile' => ['username' => 'margaret'], 'team' => 'research'],
        ];

        $keyed = Arrays::keyBy($users, 'profile.username');
        $grouped = Arrays::groupBy($users, fn ($user) => $user['team']);

        $this->assertArrayHasKey('ada', $keyed);
        $this->assertSame($users[0], $keyed['ada']);
        $this->assertCount(2, $grouped['core']);
        $this->assertCount(1, $grouped['research']);
    }

    public function testSortBySupportsCallableAndDescendingOrder(): void
    {
        $items = [
            ['id' => 1, 'score' => 70],
            ['id' => 2, 'score' => 90],
            ['id' => 3, 'score' => 80],
        ];

        $ascending = Arrays::sortBy($items, 'score');
        $descending = Arrays::sortBy($items, fn ($item) => $item['score'], true);

        $this->assertSame([1, 3, 2], array_column($ascending, 'id'));
        $this->assertSame([2, 3, 1], array_column($descending, 'id'));
    }

    public function testAssociativeChecks(): void
    {
        $this->assertTrue(Arrays::isAssoc(['a' => 1, 'b' => 2]));
        $this->assertFalse(Arrays::isAssoc([10, 20]));
        $this->assertTrue(Arrays::isSequential([10, 20]));
        $this->assertFalse(Arrays::isSequential(['a' => 1]));
        $this->assertFalse(Arrays::isAssoc([]));
    }

    public function testRandomReturnsElementsWithinBounds(): void
    {
        $values = ['a', 'b', 'c'];

        $single = Arrays::random($values);
        $this->assertContains($single, $values);

        $multi = Arrays::random($values, 2);
        $this->assertCount(2, $multi);
        foreach ($multi as $value) {
            $this->assertContains($value, $values);
        }

        $all = Arrays::random($values, 10);
        $this->assertCount(3, $all);
        $this->assertEqualsCanonicalizing($values, $all);
    }

    public function testShufflePreservesKeysAndValues(): void
    {
        $assoc = ['first' => 1, 'second' => 2, 'third' => 3];

        $shuffled = Arrays::shuffle($assoc);

        $this->assertEqualsCanonicalizing(array_keys($assoc), array_keys($shuffled));
        $this->assertEqualsCanonicalizing($assoc, $shuffled);
    }

    public function testChunkAndCollapse(): void
    {
        $array = [1, 2, 3, 4, 5];
        $chunks = Arrays::chunk($array, 2);
        $mixed = [[1, 2], 'not-array', [3, 4]];

        $this->assertSame([[1, 2], [3, 4], [5]], $chunks);
        $this->assertSame([1, 2, 3, 4], Arrays::collapse($mixed));
    }

    public function testCrossJoinCreatesCartesianProduct(): void
    {
        $result = Arrays::crossJoin([1, 2], ['a', 'b']);

        $this->assertEqualsCanonicalizing([
            [1, 'a'],
            [1, 'b'],
            [2, 'a'],
            [2, 'b'],
        ], $result);
    }

    public function testDivideSplitsArrayIntoKeysAndValues(): void
    {
        $data = ['a' => 10, 'b' => 20];

        [$keys, $values] = Arrays::divide($data);

        $this->assertSame(['a', 'b'], $keys);
        $this->assertSame([10, 20], $values);
    }

    public function testReplaceRecursiveAppliesMultipleOverrides(): void
    {
        $original = ['config' => ['debug' => false, 'cache' => ['enabled' => true]]];
        $updated = Arrays::replaceRecursive(
            $original,
            ['config' => ['debug' => true]],
            ['config' => ['cache' => ['enabled' => false]]]
        );

        $this->assertSame([
            'config' => ['debug' => true, 'cache' => ['enabled' => false]],
        ], $updated);
    }

    public function testDuplicatesReturnsCountsForRepeatedValues(): void
    {
        $values = ['apple', 'banana', 'apple', 'orange', 'banana', 'banana'];

        $this->assertSame([
            'apple' => 2,
            'banana' => 3,
        ], Arrays::duplicates($values));
    }

    public function testMapPreservesKeysAndProvidesKeyToCallback(): void
    {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];

        $result = Arrays::map($input, function (int $value, string $key): string {
            return $key . ':' . ($value * 2);
        });

        $this->assertSame([
            'a' => 'a:2',
            'b' => 'b:4',
            'c' => 'c:6',
        ], $result);
    }

    public function testMapRecursiveTransformsNestedScalars(): void
    {
        $input = [
            'name' => 'Ada',
            'scores' => [10, 20],
            'meta' => ['active' => true],
        ];

        $result = Arrays::mapRecursive($input, function ($value): string {
            return is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        });

        $this->assertSame([
            'name' => 'Ada',
            'scores' => ['10', '20'],
            'meta' => ['active' => 'true'],
        ], $result);
    }

    public function testWrapNormalizesValuesToArray(): void
    {
        $this->assertSame([], Arrays::wrap(null));
        $this->assertSame(['value'], Arrays::wrap('value'));
        $array = [1, 2];
        $this->assertSame($array, Arrays::wrap($array));
    }

    public function testQueryHelpersRoundTrip(): void
    {
        $params = ['name' => 'Ada Lovelace', 'language' => 'PHP', 'tags' => ['math', 'code']];

        $query = Arrays::toQuery($params);
        $parsed = Arrays::fromQuery($query);

        $this->assertSame($params['name'], $parsed['name']);
        $this->assertSame($params['language'], $parsed['language']);
        $this->assertSame($params['tags'], $parsed['tags']);
    }
}

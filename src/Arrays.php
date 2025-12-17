<?php

namespace PhpHelper;

class Arrays
{
    /**
     * Get value from array using dot notation
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set value in array using dot notation
     */
    public static function set(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Check if key exists using dot notation
     */
    public static function has(array $array, string $key): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Remove value from array using dot notation
     */
    public static function forget(array &$array, string $key): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                return;
            }
            $array = &$array[$key];
        }

        unset($array[array_shift($keys)]);
    }

    /**
     * Flatten multi-dimensional array
     */
    public static function flatten(array $array, int $depth = INF): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, self::flatten($item, $depth - 1));
            }
        }

        return $result;
    }

    /**
     * Flatten array with dot notation keys
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, self::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get only specified keys from array
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Get all except specified keys from array
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Get first element matching callback
     */
    public static function first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return $default;
            }
            return reset($array);
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get last element matching callback
     */
    public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if (is_null($callback)) {
            return empty($array) ? $default : end($array);
        }

        return self::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Filter array using callback
     */
    public static function where(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Filter array where value equals
     */
    public static function whereEquals(array $array, string $key, mixed $value): array
    {
        return array_filter($array, function ($item) use ($key, $value)
        {
            return self::get($item, $key) === $value;
        });
    }

    /**
     * Filter array where value is in array
     */
    public static function whereIn(array $array, string $key, array $values): array
    {
        return array_filter($array, function ($item) use ($key, $values)
        {
            return in_array(self::get($item, $key), $values, true);
        });
    }

    /**
     * Filter array where value is not in array
     */
    public static function whereNotIn(array $array, string $key, array $values): array
    {
        return array_filter($array, function ($item) use ($key, $values)
        {
            return !in_array(self::get($item, $key), $values, true);
        });
    }

    /**
     * Pluck single column from array
     */
    public static function pluck(array $array, string $value, ?string $key = null): array
    {
        $results = [];

        foreach ($array as $item) {
            $itemValue = self::get($item, $value);

            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $results[self::get($item, $key)] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Key array by specific field value (supports dot notation)
     * 
     * @example
     * $users = [
     *     ['id' => 1, 'name' => 'John', 'profile' => ['username' => 'john123']],
     *     ['id' => 2, 'name' => 'Jane', 'profile' => ['username' => 'jane456']]
     * ];
     * $byUsername = ArrayHelper::keyBy($users, 'profile.username');
     * // Results in: ['john123' => [...], 'jane456' => [...]]
     */
    public static function keyBy(array $array, string|callable $key): array
    {
        $results = [];

        foreach ($array as $item) {
            if (is_callable($key)) {
                $keyValue = $key($item);
            } else {
                $keyValue = self::get($item, $key);
            }

            if ($keyValue !== null) {
                $results[$keyValue] = $item;
            }
        }

        return $results;
    }

    /**
     * Group array by key
     */
    public static function groupBy(array $array, string|callable $key): array
    {
        $results = [];

        foreach ($array as $item) {
            if (is_callable($key)) {
                $groupKey = $key($item);
            } else {
                $groupKey = self::get($item, $key);
            }

            $results[$groupKey][] = $item;
        }

        return $results;
    }

    /**
     * Group keys by their values
     *
     * @example
     * $input = ['a' => 'red', 'b' => 'blue', 'c' => 'red'];
     * $grouped = Arrays::groupByValue($input);
     * // Results in: ['red' => ['a', 'c'], 'blue' => ['b']]
     */
    public static function groupByValue(array $array): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            $groupKey = is_scalar($value) ? (string) $value : json_encode($value);
            $results[$groupKey][] = $key;
        }

        return $results;
    }

    /**
     * Sum values using optional key or callback
     */
    public static function sumBy(array $array, string|callable|null $selector = null): float|int
    {
        $sum = 0;

        foreach ($array as $index => $item) {
            if (is_null($selector)) {
                $value = $item;
            } elseif (is_callable($selector)) {
                $value = $selector($item, $index);
            } else {
                $value = is_array($item) ? self::get($item, $selector) : null;
            }

            if (is_numeric($value)) {
                $sum += $value + 0;
            }
        }

        return $sum;
    }

    /**
     * Sort array by key
     */
    public static function sortBy(array $array, string|callable $key, bool $descending = false): array
    {
        $results = [];

        foreach ($array as $originalKey => $item) {
            if (is_callable($key)) {
                $sortKey = $key($item);
            } else {
                $sortKey = self::get($item, $key);
            }

            $results[$originalKey] = $sortKey;
        }

        $descending ? arsort($results) : asort($results);

        $sorted = [];
        foreach ($results as $originalKey => $value) {
            $sorted[$originalKey] = $array[$originalKey];
        }

        return $sorted;
    }

    /**
     * Check if array is associative
     */
    public static function isAssoc(array $array): bool
    {
        return !array_is_list($array);
    }

    /**
     * Check if array is sequential
     */
    public static function isSequential(array $array): bool
    {
        return array_is_list($array);
    }

    /**
     * Get random element(s) from array
     */
    public static function random(array $array, int $number = 1): mixed
    {
        $count = count($array);

        if ($number > $count) {
            $number = $count;
        }

        if ($number === 1) {
            return $array[array_rand($array)];
        }

        $keys    = array_rand($array, $number);
        $results = [];

        foreach ($keys as $key) {
            $results[] = $array[$key];
        }

        return $results;
    }

    /**
     * Shuffle array preserving keys
     */
    public static function shuffle(array $array): array
    {
        $keys = array_keys($array);
        shuffle($keys);

        $shuffled = [];
        foreach ($keys as $key) {
            $shuffled[$key] = $array[$key];
        }

        return $shuffled;
    }

    /**
     * Chunk array into smaller arrays
     */
    public static function chunk(array $array, int $size, bool $preserveKeys = false): array
    {
        return array_chunk($array, $size, $preserveKeys);
    }

    /**
     * Collapse array of arrays into single array
     */
    public static function collapse(array $array): array
    {
        $results = [];

        foreach ($array as $values) {
            if (!is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * Cross join arrays
     */
    public static function crossJoin(array ...$arrays): array
    {
        $results = [[]];

        foreach ($arrays as $index => $array) {
            $append = [];

            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;
                    $append[]        = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    /**
     * Divide array into keys and values
     */
    public static function divide(array $array): array
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Recursively replace values in array
     */
    public static function replaceRecursive(array $array, array ...$replacements): array
    {
        foreach ($replacements as $replacement) {
            $array = array_replace_recursive($array, $replacement);
        }

        return $array;
    }

    /**
     * Get duplicate values from array
     */
    public static function duplicates(array $array): array
    {
        $counts = array_count_values($array);

        return array_filter($counts, function ($count)
        {
            return $count > 1;
        });
    }

    /**
     * Map array preserving keys
     */
    public static function map(array $array, callable $callback): array
    {
        $keys  = array_keys($array);
        $items = array_map($callback, $array, $keys);

        return array_combine($keys, $items);
    }

    /**
     * Recursively map array
     */
    public static function mapRecursive(array $array, callable $callback): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::mapRecursive($value, $callback);
            } else {
                $array[$key] = $callback($value, $key);
            }
        }

        return $array;
    }

    /**
     * Wrap value in array if not already an array
     */
    public static function wrap(mixed $value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Convert array to query string
     */
    public static function toQuery(array $array): string
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Parse query string to array
     */
    public static function fromQuery(string $query): array
    {
        parse_str($query, $result);
        return $result;
    }
}

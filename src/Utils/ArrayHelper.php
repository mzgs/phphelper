<?php

namespace PhpHelper\Utils;

class ArrayHelper
{
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    public static function set(array &$array, string $key, mixed $value): void
    {
        if (strpos($key, '.') === false) {
            $array[$key] = $value;
            return;
        }

        $keys    = explode('.', $key);
        $current = &$array;
        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }

    public static function remove(array &$array, string $key): void
    {
        if (strpos($key, '.') === false) {
            unset($array[$key]);
            return;
        }

        $keys    = explode('.', $key);
        $last    = array_pop($keys);
        $current = &$array;

        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                return;
            }
            $current = &$current[$segment];
        }

        unset($current[$last]);
    }

    public static function has(array $array, string $key): bool
    {
        if (strpos($key, '.') === false) {
            return array_key_exists($key, $array);
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }

        return true;
    }

    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    public static function pluck(array $array, string $key, ?string $keyBy = null): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item) && !is_object($item)) {
                continue;
            }

            $value = is_object($item) ? $item->{$key} : $item[$key];

            if ($keyBy !== null) {
                $keyValue          = is_object($item) ? $item->{$keyBy} : $item[$keyBy];
                $result[$keyValue] = $value;
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }

    public static function groupBy(array $array, string $key): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item) && !is_object($item)) {
                continue;
            }

            $value = is_object($item) ? $item->{$key} : $item[$key];

            if (!isset($result[$value])) {
                $result[$value] = [];
            }

            $result[$value][] = $item;
        }

        return $result;
    }

    public static function keyBy(array $array, string $key): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item) && !is_object($item)) {
                continue;
            }

            $value          = is_object($item) ? $item->{$key} : $item[$key];
            $result[$value] = $item;
        }

        return $result;
    }

    public static function where(array $array, string $key, mixed $value, string $operator = '='): array
    {
        return array_filter($array, function ($item) use ($key, $value, $operator)
        {
            if (!is_array($item) && !is_object($item)) {
                return false;
            }

            $itemValue = is_object($item) ? $item->{$key} : $item[$key];

            switch ($operator) {
                case '=':
                    return $itemValue == $value;
                case '===':
                    return $itemValue === $value;
                case '!=':
                    return $itemValue != $value;
                case '!==':
                    return $itemValue !== $value;
                case '>':
                    return $itemValue > $value;
                case '>=':
                    return $itemValue >= $value;
                case '<':
                    return $itemValue < $value;
                case '<=':
                    return $itemValue <= $value;
                default:
                    return false;
            }
        });
    }

    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else if ($depth === 1) {
                foreach ($item as $value) {
                    $result[] = $value;
                }
            } else {
                $result = array_merge($result, self::flatten($item, $depth - 1));
            }
        }

        return $result;
    }

    public static function chunk(array $array, int $size, bool $preserveKeys = false): array
    {
        return array_chunk($array, $size, $preserveKeys);
    }

    public static function random(array $array, ?int $number = null): mixed
    {
        if ($number === null) {
            return $array[array_rand($array)];
        }

        $keys = array_rand($array, min($number, count($array)));
        $keys = is_array($keys) ? $keys : [$keys];

        return array_intersect_key($array, array_flip($keys));
    }

    public static function shuffle(array $array, bool $preserveKeys = false): array
    {
        if ($preserveKeys) {
            $keys = array_keys($array);
            shuffle($keys);
            $result = [];
            foreach ($keys as $key) {
                $result[$key] = $array[$key];
            }
            return $result;
        }

        shuffle($array);
        return $array;
    }

    public static function first(array $array, mixed $default = null): mixed
    {
        return empty($array) ? $default : reset($array);
    }

    public static function last(array $array, mixed $default = null): mixed
    {
        return empty($array) ? $default : end($array);
    }

    public static function whereFirst(array $array, string $key, mixed $value, string $operator = '='): mixed
    {
        $result = self::where($array, $key, $value, $operator);
        return empty($result) ? null : reset($result);
    }

    public static function whereLast(array $array, string $key, mixed $value, string $operator = '='): mixed
    {
        $result = self::where($array, $key, $value, $operator);
        return empty($result) ? null : end($result);
    }

    public static function unique(array $array, ?string $key = null): array
    {
        if ($key === null) {
            return array_unique($array);
        }

        $seen = [];
        return array_filter($array, function ($item) use ($key, &$seen)
        {
            if (!is_array($item) && !is_object($item)) {
                return false;
            }

            $value = is_object($item) ? $item->{$key} : $item[$key];

            if (in_array($value, $seen)) {
                return false;
            }

            $seen[] = $value;
            return true;
        });
    }
}
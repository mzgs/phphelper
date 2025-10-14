<?php

class Date
{
    /**
     * Human friendly relative time (e.g., "2 hours ago", "in 3 days").
     *
     * @param \DateTimeInterface|int|string $timestamp DateTime, unix timestamp, or parseable string
     */
    public static function ago(\DateTimeInterface|int|string $timestamp, bool $full = false): string
    {
        if ($timestamp instanceof \DateTimeInterface) {
            $datetime = (new \DateTimeImmutable())->setTimestamp($timestamp->getTimestamp());
        } elseif (is_int($timestamp)) {
            $datetime = (new \DateTimeImmutable())->setTimestamp($timestamp);
        } else {
            $datetime = new \DateTimeImmutable($timestamp);
        }

        $now = new \DateTimeImmutable();
        $interval = $now->diff($datetime);

        $units = [
            'y' => 'year',
            'm' => 'month',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];

        $parts = [];
        foreach ($units as $key => $unit) {
            $value = $interval->$key;
            if ($value > 0) {
                $parts[] = $value . ' ' . $unit . ($value > 1 ? 's' : '');
                if (!$full) {
                    break; // only most significant part unless full
                }
            }
        }

        if (empty($parts)) {
            return 'just now';
        }

        // invert === 1 means the compared time is in the past relative to now
        $suffix = $interval->invert ? ' ago' : ' from now';

        if ($full && count($parts) > 1) {
            $last = array_pop($parts);
            return implode(', ', $parts) . ' and ' . $last . $suffix;
        }

        return ($full ? implode(', ', $parts) : $parts[0]) . $suffix;
    }

    /**
     * Easy date/time formatting with sensible presets.
     *
     * Accepts a DateTime, unix timestamp, or parseable string.
     * If $format matches a preset key, that preset is used. Otherwise, it is
     * treated as a raw PHP date() format string passed to DateTime::format().
     *
     * Presets:
     *  - "date"              => Y-m-d
     *  - "datetime"          => Y-m-d H:i
     *  - "datetime_seconds"  => Y-m-d H:i:s
     *  - "time"              => H:i
     *  - "time_seconds"      => H:i:s
     *  - "iso"               => DateTimeInterface::ATOM (ISO 8601)
     *  - "rfc2822"           => DATE_RFC2822
     *  - "rss"               => DATE_RSS
     *  - "human"             => j M Y
     *  - "human_full"        => j F Y, H:i
     */
    public static function format(\DateTimeInterface|int|string $timestamp, string $format = 'datetime', ?string $timezone = null): string
    {
        // Normalize input to immutable DateTime
        if ($timestamp instanceof \DateTimeInterface) {
            $dt = (new \DateTimeImmutable())->setTimestamp($timestamp->getTimestamp());
        } elseif (is_int($timestamp)) {
            $dt = (new \DateTimeImmutable())->setTimestamp($timestamp);
        } else {
            $dt = new \DateTimeImmutable($timestamp);
        }

        // Optional timezone adjustment
        if ($timezone !== null && $timezone !== '') {
            try {
                $tz = new \DateTimeZone($timezone);
                $dt = $dt->setTimezone($tz);
            } catch (\Throwable) {
                // Ignore invalid timezone, keep original
            }
        }

        // Preset patterns
        $presets = [
            'date' => 'Y-m-d',
            'datetime' => 'Y-m-d H:i',
            'datetime_seconds' => 'Y-m-d H:i:s',
            'time' => 'H:i',
            'time_seconds' => 'H:i:s',
            'human' => 'j M Y',
            'human_full' => 'j F Y, H:i',
        ];

        // Special named formats that map to PHP date() constants
        if ($format === 'iso') {
            return $dt->format(DATE_ATOM);
        }
        if ($format === 'rfc2822') {
            return $dt->format(DATE_RFC2822);
        }
        if ($format === 'rss') {
            return $dt->format(DATE_RSS);
        }

        $pattern = $presets[$format] ?? $format;
        return $dt->format($pattern);
    }

    /**
     * Get a UNIX timestamp (seconds since epoch) from input.
     *
     * Accepts a DateTimeInterface, an integer timestamp, a parseable string, or null (uses now).
     * If a timezone is provided, it is used only when parsing string inputs that lack timezone info.
     */
    public static function timestamp(\DateTimeInterface|int|string|null $value = null, ?string $timezone = null): int
    {
        if ($value === null) {
            return time();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_int($value)) {
            return $value;
        }

        $tz = null;
        if ($timezone !== null && $timezone !== '') {
            try {
                $tz = new \DateTimeZone($timezone);
            } catch (\Throwable) {
                $tz = null; // ignore invalid timezone
            }
        }

        $dt = new \DateTimeImmutable($value, $tz);
        return $dt->getTimestamp();
    }
}

 

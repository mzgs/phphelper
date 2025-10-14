<?php

class Format
{
    /**
     * Format bytes into a human readable string.
     *
     * Examples: 0 -> "0 B", 1536 -> "1.50 KB"
     */
    public static function bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        if ($bytes <= 0) {
            return '0 B';
        }

        $pow = (int) floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}


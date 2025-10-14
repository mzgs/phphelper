<?php
class Files
{
    /**
     * Read file contents
     */
    public static function read(string $path): string|false
    {
        if (!file_exists($path) || !is_readable($path)) {
            return false;
        }
        return file_get_contents($path);
    }

    /**
     * Write contents to file
     */
    public static function write(string $path, string $content, int $flags = 0): int|false
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return file_put_contents($path, $content, $flags);
    }

    /**
     * Append contents to file
     */
    public static function append(string $path, string $content): int|false
    {
        return self::write($path, $content, FILE_APPEND);
    }

    /**
     * Delete file
     */
    public static function delete(string $path): bool
    {
        if (file_exists($path) && is_file($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * Copy file
     */
    public static function copy(string $source, string $dest): bool
    {
        if (!file_exists($source)) {
            return false;
        }
        $destDir = dirname($dest);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        return copy($source, $dest);
    }

    /**
     * Move/rename file
     */
    public static function move(string $source, string $dest): bool
    {
        if (!file_exists($source)) {
            return false;
        }
        $destDir = dirname($dest);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        return rename($source, $dest);
    }

    /**
     * Check if file exists
     */
    public static function exists(string $path): bool
    {
        return file_exists($path) && is_file($path);
    }

    /**
     * Get file size in bytes
     */
    public static function size(string $path): int|false
    {
        if (!self::exists($path)) {
            return false;
        }
        return filesize($path);
    }

    /**
     * Get file size formatted as human-readable string
     */
    public static function sizeFormatted(string $path): string|false
    {
        $bytes = self::size($path);
        if ($bytes === false) {
            return false;
        }

        return Format::bytes($bytes, 2, 'binary');
    }

    /**
     * Get file extension
     */
    public static function extension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Get file mime type
     */
    public static function mimeType(string $path): string|false
    {
        if (!self::exists($path)) {
            return false;
        }
        return mime_content_type($path);
    }

    /**
     * Get file modification time
     */
    public static function modifiedTime(string $path): int|false
    {
        if (!self::exists($path)) {
            return false;
        }
        return filemtime($path);
    }

    /**
     * Create directory recursively
     */
    public static function createDirectory(string $path, int $permissions = 0755): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, $permissions, true);
        }
        return true;
    }

    /**
     * Delete directory recursively
     */
    public static function deleteDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $path . '/' . $file;
            if (is_dir($filePath)) {
                self::deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        return rmdir($path);
    }

    /**
     * List files in directory
     */
    public static function listFiles(string $path, string $pattern = '*'): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $files = glob($path . '/' . $pattern);
        return array_filter($files, 'is_file');
    }

    /**
     * List directories
     */
    public static function listDirectories(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $dirs = glob($path . '/*', GLOB_ONLYDIR);
        return $dirs ?: [];
    }

    /**
     * Read JSON file
     */
    public static function readJson(string $path): mixed
    {
        $content = self::read($path);
        if ($content === false) {
            return false;
        }

        return json_decode($content, true);
    }

    /**
     * Write JSON file
     */
    public static function writeJson(string $path, mixed $data, int $flags = JSON_PRETTY_PRINT): int|false
    {
        $json = json_encode($data, $flags);
        if ($json === false) {
            return false;
        }

        return self::write($path, $json);
    }

    /**
     * Read CSV file
     */
    public static function readCsv(string $path, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): array|false
    {
        if (!self::exists($path)) {
            return false;
        }

        $data = [];
        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
                $data[] = $row;
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Write CSV file
     */
    public static function writeCsv(string $path, array $data, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): bool
    {
        $handle = fopen($path, 'w');
        if ($handle === false) {
            return false;
        }

        foreach ($data as $row) {
            fputcsv($handle, $row, $delimiter, $enclosure, $escape);
        }

        fclose($handle);
        return true;
    }

    /**
     * Get file hash
     */
    public static function hash(string $path, string $algo = 'sha256'): string|false
    {
        if (!self::exists($path)) {
            return false;
        }

        return hash_file($algo, $path);
    }

    /**
     * Check if path is absolute
     */
    public static function isAbsolute(string $path): bool
    {
        return $path[0] === '/' || preg_match('/^[A-Z]:/i', $path);
    }

    /**
     * Normalize path separators
     */
    public static function normalizePath(string $path): string
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Join path parts
     */
    public static function joinPaths(string ...$parts): string
    {
        return implode(DIRECTORY_SEPARATOR, array_map(function ($part)
        {
            return rtrim($part, '/\\');
        }, $parts));
    }
}

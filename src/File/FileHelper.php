<?php

namespace PhpHelper\File;

class FileHelper
{
    private static $defaultPermissions = 0755;

    public static function read(string $path): string|false
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: {$path}");
        }
        return file_get_contents($path);
    }

    public static function write(string $path, string $content, bool $append = false): bool
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            self::createDirectory($directory);
        }

        $flag = $append ? FILE_APPEND : 0;
        return file_put_contents($path, $content, $flag) !== false;
    }

    public static function delete(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }
        return unlink($path);
    }

    public static function copy(string $source, string $destination): bool
    {
        if (!file_exists($source)) {
            throw new \Exception("Source file not found: {$source}");
        }

        $directory = dirname($destination);
        if (!is_dir($directory)) {
            self::createDirectory($directory);
        }

        return copy($source, $destination);
    }

    public static function move(string $source, string $destination): bool
    {
        if (self::copy($source, $destination)) {
            return self::delete($source);
        }
        return false;
    }

    public static function createDirectory(string $path, int $permissions = null): bool
    {
        if (is_dir($path)) {
            return true;
        }

        $permissions = $permissions ?? self::$defaultPermissions;
        return mkdir($path, $permissions, true);
    }

    public static function deleteDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $path . DIRECTORY_SEPARATOR . $file;
            is_dir($filePath) ? self::deleteDirectory($filePath) : self::delete($filePath);
        }

        return rmdir($path);
    }

    public static function getExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    public static function getMimeType(string $path): string|false
    {
        return mime_content_type($path);
    }

    public static function getSize(string $path): int|false
    {
        return filesize($path);
    }

    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    public static function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    public static function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    public static function getModificationTime(string $path): int|false
    {
        return filemtime($path);
    }

    public static function getFiles(
        string $directory,
        string $pattern = '*',
        bool $recursive = false,
    ): array {
        $directory = rtrim($directory, '/\\');
        $results   = [];

        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory)
            );
        } else {
            $iterator = new \DirectoryIterator($directory);
        }

        foreach ($iterator as $file) {
            if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
                $results[] = $file->getPathname();
            }
        }

        return $results;
    }

    public static function upload(
        array $file,
        string $destination,
        array $allowedTypes = [],
        int $maxSize = 5242880,
    ): array {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \Exception('Invalid upload');
        }

        if ($file['size'] > $maxSize) {
            throw new \Exception('File size exceeds limit');
        }

        if (!empty($allowedTypes)) {
            $type = self::getMimeType($file['tmp_name']);
            if (!in_array($type, $allowedTypes)) {
                throw new \Exception('File type not allowed');
            }
        }

        $directory = dirname($destination);
        if (!is_dir($directory)) {
            self::createDirectory($directory);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => true,
                'path'    => $destination,
                'size'    => $file['size'],
                'type'    => $file['type'],
                'name'    => $file['name'],
            ];
        }

        throw new \Exception('Failed to move uploaded file');
    }
}
<?php

class Http
{
    /**
     * Redirect to a URL with optional status code.
     */
    public static function redirect(string $url, int $statusCode = 302, bool $exit = true): void
    {
        if (!headers_sent()) {
            header('Location: ' . $url, true, $statusCode);
        } else {
            echo '<script>window.location.href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '";</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        }

        if ($exit) {
            exit();
        }
    }

    /**
     * Stream a file as a download. Returns false if file is missing, otherwise exits.
     * Note: No return type because successful paths end the request with exit().
     */
    public static function download(string $filename, string $mimetype = 'application/octet-stream')
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }
        $base = basename($filename);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Disposition: attachment; filename=' . $base);
        header('Content-Length: ' . filesize($filename));
        header('Content-Type: ' . $mimetype);
        readfile($filename);
        exit();
    }
}


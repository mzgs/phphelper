<?php

class ErrorHandler
{
    /** @var array<string,mixed> */
    private array $options;

    /**
     * Create and (by default) register global handlers.
     *
     * Options:
     *  - display (bool): force display errors (default true)
     *  - report (int): error_reporting level (default E_ALL)
     *  - context (int): lines of context around error line (default 6 before, 4 after)
     *  - show_trace (bool): include backtrace (default true)
     */
    public function __construct(array $options = [], bool $registerGlobal = true)
    {
        $this->options = $options + [
            'display' => true,
            'report' => E_ALL,
            'context_before' => 6,
            'context_after' => 4,
            'show_trace' => true,
            // Render as an on-page overlay instead of a full page
            'overlay' => false,
        ];

        if ($registerGlobal) {
            $this->register();
        }
    }

    /**
     * Convenient static helper to enable the handler in one call.
     */
    public static function enable(array $options = []): self
    {
        return new self($options, true);
    }

    /**
     * Register error, exception and shutdown handlers.
     */
    public function register(): void
    {
        // Configure PHP to report and display errors (for development)
        if ($this->options['display']) {
            ini_set('display_errors', '1');
        }
        error_reporting((int)$this->options['report']);

        set_error_handler(function (int $severity, string $message, string $file = 'unknown', int $line = 0): bool {
            // Respect @-operator
            if (!(error_reporting() & $severity)) {
                return false;
            }

            $this->renderThrowable($this->errorException($message, $severity, $file, $line));
            // Returning true prevents PHP internal handler
            return true;
        });

        set_exception_handler(function ($e): void {
            if ($e instanceof \Throwable) {
                $this->renderThrowable($e);
                // Uncaught exceptions should terminate execution
                exit(255);
            }
            // Fallback for non-Throwable
            $this->renderThrowable(new \RuntimeException('Unknown exception type thrown'));
            exit(255);
        });

        register_shutdown_function(function (): void {
            $err = error_get_last();
            if ($err && $this->isFatal($err['type'] ?? 0)) {
                $e = $this->errorException($err['message'] ?? 'Fatal error', (int)($err['type'] ?? E_ERROR), $err['file'] ?? 'unknown', (int)($err['line'] ?? 0));
                $this->renderThrowable($e);
                // Ensure fatal shutdown renders as the last output
                exit(255);
            }
        });
    }

    private function isCli(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    private function isFatal(int $type): bool
    {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
    }

    private function errorLevelToString(int $severity): string
    {
        return match ($severity) {
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            default => 'E_UNKNOWN',
        };
    }

    private function errorException(string $message, int $severity, string $file, int $line): \ErrorException
    {
        return new \ErrorException($message, 0, $severity, $file, $line);
    }

    private function renderThrowable(\Throwable $e): void
    {
        if ($this->isCli()) {
            $this->renderCli($e);
        } else {
            $this->renderHtml($e);
        }
    }

    private function renderCli(\Throwable $e): void
    {
        // Avoid nested output if headers already started for some reason in CLI
        $type = $e instanceof \ErrorException ? $this->errorLevelToString($e->getSeverity()) : get_class($e);
        $file = $e->getFile();
        $line = $e->getLine();

        $header = $type . ': ' . $e->getMessage();
        $separator = str_repeat('=', max(30, strlen($header)));
        fwrite(STDERR, $separator . PHP_EOL);
        fwrite(STDERR, $header . PHP_EOL);
        fwrite(STDERR, $separator . PHP_EOL);
        fwrite(STDERR, 'File: ' . $file . PHP_EOL);
        fwrite(STDERR, 'Line: ' . $line . PHP_EOL);

        $snippet = $this->getCodeSnippet($file, $line, (int)$this->options['context_before'], (int)$this->options['context_after']);
        if ($snippet !== null) {
            fwrite(STDERR, PHP_EOL . 'Code:' . PHP_EOL);
            foreach ($snippet as $row) {
                [$ln, $code, $highlight] = $row;
                $prefix = $highlight ? ' > ' : '   ';
                fwrite(STDERR, sprintf('%s%5d | %s', $prefix, $ln, rtrim($code)) . PHP_EOL);
            }
        }

        if ($this->options['show_trace']) {
            fwrite(STDERR, PHP_EOL . 'Trace:' . PHP_EOL);
            fwrite(STDERR, $this->formatTraceText($e) . PHP_EOL);
        }
    }

    private function renderHtml(\Throwable $e): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        $type = $e instanceof \ErrorException ? $this->errorLevelToString($e->getSeverity()) : get_class($e);
        $file = $e->getFile();
        $line = $e->getLine();
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $snippet = $this->getCodeSnippet($file, $line, (int)$this->options['context_before'], (int)$this->options['context_after']);
        $traceHtml = $this->options['show_trace'] ? $this->formatTraceHtml($e) : '';

        if (!empty($this->options['overlay'])) {
            // Render as an overlay that can sit on top of any existing page content
            $this->renderHtmlOverlay($type, $message, $file, $line, $snippet, $traceHtml);
            return;
        }

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Error</title>'
            . '<style>'
            . 'html,body{margin:0;padding:0;background:#0e1116;color:#e6edf3;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Ubuntu,Cantarell,\"Helvetica Neue\",Arial,\"Apple Color Emoji\",\"Segoe UI Emoji\";}'
            . '.container{max-width:1000px;margin:24px auto;padding:16px;}'
            . '.card{background:#161b22;border:1px solid #30363d;border-radius:8px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.3);}'
            . '.bar{background:#da3633;color:#fff;padding:12px 16px;font-size:16px;font-weight:600;display:flex;gap:12px;align-items:center;}'
            . '.meta{padding:12px 16px;border-bottom:1px solid #30363d;color:#8b949e;font-size:13px;}'
            . '.code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,\"Liberation Mono\",\"Courier New\",monospace;font-size:13px;}'
            . '.code pre{margin:0;white-space:pre;overflow:auto;background:#0b1021;color:#e6edf3;}'
            . '.row{display:flex;}'
            . '.ln{user-select:none;padding:0 12px 0 16px;background:#0b1021;color:#8b949e;text-align:right;border-right:1px solid #30363d;min-width:64px;}'
            . '.src{padding:0 16px;white-space:pre;overflow:auto;flex:1;}'
            . '.hl{background:#3a1d1d;}'
            . '.trace{padding:12px 16px;color:#c9d1d9;font-size:13px;line-height:1.5;}'
            . '.trace .item{border-top:1px solid #30363d;padding:8px 0;}'
            . '.trace .item:first-child{border-top:none;}'
            . '.small{opacity:.85;font-size:12px;}'
            . '</style></head><body><div class="container">'
            . '<div class="card">'
            . '<div class="bar">' . htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ': ' . $message . '</div>'
            . '<div class="meta">File: ' . htmlspecialchars($file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ' &nbsp;•&nbsp; Line: ' . (int)$line . '</div>'
            . '<div class="code">';

        if ($snippet !== null) {
            echo '<pre>';
            foreach ($snippet as $row) {
                [$ln, $code, $highlight] = $row;
                $lnHtml = '<span class="ln' . ($highlight ? ' hl' : '') . '">' . (int)$ln . '</span>';
                $srcHtml = '<span class="src' . ($highlight ? ' hl' : '') . '">' . htmlspecialchars(rtrim($code, "\r\n"), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
                echo '<div class="row">' . $lnHtml . $srcHtml . '</div>';
            }
            echo '</pre>';
        } else {
            echo '<div class="meta small">Source not available</div>';
        }

        echo '</div>' . $traceHtml . '</div></div></body></html>';
    }

    /**
     * Render an overlay with a close button instead of a full page.
     *
     * @param string $type
     * @param string $message (already HTML-escaped)
     * @param string $file
     * @param int    $line
     * @param array<int, array{0:int,1:string,2:bool}>|null $snippet
     * @param string $traceHtml (already HTML-escaped markup)
     */
    private function renderHtmlOverlay(string $type, string $message, string $file, int $line, ?array $snippet, string $traceHtml): void
    {
        $fileHtml = htmlspecialchars($file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lineHtml = (string)(int)$line;

        // Styles scoped with `eh-` prefix to avoid collisions; very high z-index.
        echo '<style id="php-error-overlay-style">'
            . '.eh-overlay{position:fixed;inset:0;display:flex;align-items:flex-start;justify-content:center;padding:24px;z-index:2147483647;background:rgba(10,15,20,.6);backdrop-filter:blur(2px);} '
            . '.eh-modal{width:min(1000px,92vw);margin-top:24px;background:#161b22;border:1px solid #30363d;border-radius:8px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.4);color:#e6edf3;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Ubuntu,Cantarell,\"Helvetica Neue\",Arial,\"Apple Color Emoji\",\"Segoe UI Emoji\";} '
            . '.eh-bar{background:#da3633;color:#fff;padding:12px 16px;font-size:16px;font-weight:600;display:flex;align-items:center;gap:12px;justify-content:space-between;} '
            . '.eh-title{margin-right:auto;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;} '
            . '.eh-close{appearance:none;border:0;background:#0000;color:#fff;font-size:22px;line-height:1;border-radius:6px;padding:2px 8px;cursor:pointer;} '
            . '.eh-close:hover{background:rgba(255,255,255,.12);} '
            . '.eh-meta{padding:10px 16px;border-bottom:1px solid #30363d;color:#8b949e;font-size:13px;} '
            . '.eh-code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,\"Liberation Mono\",\"Courier New\",monospace;font-size:13px;} '
            . '.eh-code pre{margin:0;white-space:pre;overflow:auto;background:#0b1021;color:#e6edf3;} '
            . '.eh-row{display:flex;} '
            . '.eh-ln{user-select:none;padding:0 12px 0 16px;background:#0b1021;color:#8b949e;text-align:right;border-right:1px solid #30363d;min-width:64px;} '
            . '.eh-src{padding:0 16px;white-space:pre;overflow:auto;flex:1;} '
            . '.eh-hl{background:#3a1d1d;} '
            . '.eh-trace{padding:12px 16px;color:#c9d1d9;font-size:13px;line-height:1.5;} '
            . '.eh-trace .eh-item{border-top:1px solid #30363d;padding:8px 0;} '
            . '.eh-trace .eh-item:first-child{border-top:none;} '
            . '.eh-small{opacity:.85;font-size:12px;} '
            . '</style>';

        echo '<div class="eh-overlay" id="php-error-overlay" role="dialog" aria-modal="true" aria-labelledby="php-error-title">'
            . '<div class="eh-modal">'
            . '<div class="eh-bar">'
            . '<div class="eh-title" id="php-error-title">' . htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ': ' . $message . '</div>'
            . '<button type="button" class="eh-close" id="php-error-close" aria-label="Close">&times;</button>'
            . '</div>'
            . '<div class="eh-meta">File: ' . $fileHtml . ' &nbsp;•&nbsp; Line: ' . $lineHtml . '</div>'
            . '<div class="eh-code">';

        if ($snippet !== null) {
            echo '<pre>';
            foreach ($snippet as $row) {
                [$ln, $code, $highlight] = $row;
                $lnHtml = '<span class="eh-ln' . ($highlight ? ' eh-hl' : '') . '">' . (int)$ln . '</span>';
                $srcHtml = '<span class="eh-src' . ($highlight ? ' eh-hl' : '') . '">' . htmlspecialchars(rtrim($code, "\r\n"), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
                echo '<div class="eh-row">' . $lnHtml . $srcHtml . '</div>';
            }
            echo '</pre>';
        } else {
            echo '<div class="eh-meta eh-small">Source not available</div>';
        }

        echo '</div>' . str_replace(['class="item"'], ['class="eh-item"'], $traceHtml) . '</div></div>'
            . '<script>(function(){try{var ov=document.getElementById("php-error-overlay");if(!ov)return;var closeBtn=document.getElementById("php-error-close");function close(){if(ov&&ov.parentNode){ov.parentNode.removeChild(ov);}document.removeEventListener("keydown",onKey);}function onKey(e){if(e.key==="Escape"){close();}}if(closeBtn){closeBtn.addEventListener("click",close);}document.addEventListener("keydown",onKey);}catch(e){/* noop */}})();</script>';
    }

    /**
     * @return array<int, array{0:int,1:string,2:bool}>|null
     */
    private function getCodeSnippet(string $file, int $line, int $before, int $after): ?array
    {
        if (!is_readable($file) || $line <= 0) {
            return null;
        }

        $lines = @file($file);
        if ($lines === false) {
            return null;
        }

        $total = count($lines);
        $start = max(1, $line - $before);
        $end = min($total, $line + $after);

        $snippet = [];
        for ($i = $start; $i <= $end; $i++) {
            $snippet[] = [$i, $lines[$i - 1], $i === $line];
        }

        return $snippet;
    }

    private function formatTraceText(\Throwable $e): string
    {
        $out = [];
        $trace = $e->getTrace();
        $index = 0;
        foreach ($trace as $frame) {
            $func = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
            $loc = ($frame['file'] ?? '[internal]') . ':' . (isset($frame['line']) ? (int)$frame['line'] : '?');
            $out[] = sprintf('#%d %s (%s)', $index, $loc, $func);
            $index++;
        }
        return implode(PHP_EOL, $out);
    }

    private function formatTraceHtml(\Throwable $e): string
    {
        $trace = $e->getTrace();
        if (!$trace) {
            return '';
        }
        $html = '<div class="trace">';
        $i = 0;
        foreach ($trace as $frame) {
            $func = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
            $file = $frame['file'] ?? '[internal]';
            $line = isset($frame['line']) ? (int)$frame['line'] : '?';
            $html .= '<div class="item">#' . $i . ' '
                . htmlspecialchars((string)$file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . ':' . htmlspecialchars((string)$line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . ' <span class="small">(' . htmlspecialchars($func, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ')</span>'
                . '</div>';
            $i++;
        }
        $html .= '</div>';
        return $html;
    }
}

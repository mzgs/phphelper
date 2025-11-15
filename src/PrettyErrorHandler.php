<?php

namespace PhpHelper;

class PrettyErrorHandler
{
    /** @var array<string,mixed> */
    private array $options;

    /** @var bool */
    private bool $registered = false;

    /** @var bool */
    private bool $enabled = false;

    /** @var string|false|null */
    private string|false|null $previousDisplayErrors = null;

    /** @var int|null */
    private ?int $previousErrorReporting = null;

    /** @var callable|null */
    private $previousErrorHandler = null;

    /** @var callable|null */
    private $previousExceptionHandler = null;

    /** @var int */
    private static int $overlayCounter = 0;

    /** @var list<string> */
    private static array $deferredOutput = [];

    /** @var self|null */
    private static ?self $globalInstance = null;

    /**
     * Create and (by default) register global handlers.
     *
     * Options:
     *  - display (bool): force display errors (default true)
     *  - report (int): error_reporting level (default E_ALL)
     *  - context (int): lines of context around error line (default 6 before, 4 after)
     *  - show_trace (bool): include backtrace (default true)
     *  - overlay (bool): render as overlay instead of full page (default true)
     *  - skip_warnings (bool): bypass handler output for PHP warnings (default false)
     *  - log_errors (bool): append rendered errors to pretty_errors.txt (default false)
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
            'overlay' => true,
            'skip_warnings' => false,
            'log_errors' => false,
        ];

        if ($registerGlobal) {
            $this->register();
        }
    }

    /**
     * Convenient static helper to enable the handler in one call.
     */
    public static function init(array $options = []): self
    {
        return new self($options, true);
    }

    /**
     * Enable the global pretty error handler. Disables any existing instance first.
     */
    public static function enable(array $options = []): self
    {
        if (self::$globalInstance instanceof self) {
            self::$globalInstance->tearDown();
            self::$globalInstance = null;
        }

        return self::init($options);
    }

    /**
     * Disable the currently active pretty error handler instance, if any.
     */
    public static function disable(): void
    {
        if (self::$globalInstance instanceof self) {
            self::$globalInstance->tearDown();
            self::$globalInstance = null;
        }
    }

    /**
     * Register error, exception and shutdown handlers.
     */
    public function register(): void
    {
        if ($this->registered) {
            $this->enabled = true;
            self::$globalInstance = $this;
            return;
        }

        $this->previousDisplayErrors = ini_get('display_errors');
        $this->previousErrorReporting = error_reporting();

        // Configure PHP to report and display errors (for development)
        if ($this->options['display']) {
            ini_set('display_errors', '1');
        }
        error_reporting((int)$this->options['report']);

        $errorHandler = function (int $severity, string $message, string $file = 'unknown', int $line = 0): bool {
            if (!$this->enabled) {
                if (is_callable($this->previousErrorHandler)) {
                    return (bool)($this->previousErrorHandler)($severity, $message, $file, $line);
                }

                return false;
            }
            // Respect @-operator
            if (!(error_reporting() & $severity)) {
                return false;
            }

            if (!empty($this->options['skip_warnings']) && $this->isWarning($severity)) {
                return false;
            }

            $deferOutput = !$this->isFatal($severity);

            $this->renderThrowable($this->errorException($message, $severity, $file, $line), $deferOutput);
            // Returning true prevents PHP internal handler
            return true;
        };
        $this->previousErrorHandler = set_error_handler($errorHandler);

        $exceptionHandler = function ($e): void {
            if (!$this->enabled) {
                if (is_callable($this->previousExceptionHandler)) {
                    ($this->previousExceptionHandler)($e);
                    return;
                }

                if ($e instanceof \Throwable) {
                    throw $e;
                }

                throw new \RuntimeException('Unknown exception type thrown');
            }

            if ($e instanceof \Throwable) {
                $this->renderThrowable($e);
                // Uncaught exceptions should terminate execution
                exit(255);
            }
            // Fallback for non-Throwable
            $this->renderThrowable(new \RuntimeException('Unknown exception type thrown'));
            exit(255);
        };
        $this->previousExceptionHandler = set_exception_handler($exceptionHandler);

        register_shutdown_function(function (): void {
            if (!$this->enabled) {
                self::$deferredOutput = [];
                return;
            }
            $err = error_get_last();
            if ($err && $this->isFatal($err['type'] ?? 0)) {
                self::flushDeferredOutput();
                $e = $this->errorException($err['message'] ?? 'Fatal error', (int)($err['type'] ?? E_ERROR), $err['file'] ?? 'unknown', (int)($err['line'] ?? 0));
                $this->renderThrowable($e);
                // Ensure fatal shutdown renders as the last output
                exit(255);
            }

            self::flushDeferredOutput();
        });

        $this->registered = true;
        $this->enabled = true;
        self::$globalInstance = $this;
    }

    private function tearDown(): void
    {
        if (!$this->registered) {
            $this->enabled = false;
            return;
        }

        $this->enabled = false;

        // Restore error and exception handlers.
        restore_error_handler();
        restore_exception_handler();

        if ($this->previousDisplayErrors === false) {
            ini_restore('display_errors');
        } elseif ($this->previousDisplayErrors !== null) {
            ini_set('display_errors', $this->previousDisplayErrors);
        }

        if ($this->previousErrorReporting !== null) {
            error_reporting($this->previousErrorReporting);
        }

        $this->registered = false;
        $this->previousErrorHandler = null;
        $this->previousExceptionHandler = null;
        $this->previousDisplayErrors = null;
        $this->previousErrorReporting = null;

        self::$overlayCounter = 0;
        self::$deferredOutput = [];
    }

    private function isCli(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    private function isFatal(int $type): bool
    {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
    }

    private function isWarning(int $type): bool
    {
        return in_array($type, [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING], true);
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

    private function renderThrowable(\Throwable $e, bool $deferOutput = false): void
    {
        if (!empty($this->options['log_errors'])) {
            $this->logThrowable($e);
        }

        $displayEnabled = !empty($this->options['display']);

        $wantsHtmlOutput = $this->wantsHtmlOutput();

        $bufferOutput = $deferOutput && $wantsHtmlOutput;

        if ($bufferOutput) {
            ob_start();
        } elseif (!empty(self::$deferredOutput) && $wantsHtmlOutput) {
            self::flushDeferredOutput();
        }

        if (!$displayEnabled) {
            if ($e instanceof \ErrorException && !$this->isFatal($e->getSeverity())) {
                // Suppress output for non-fatal errors when display is disabled.
                if ($bufferOutput) {
                    ob_end_clean();
                }
                return;
            }

            $this->renderSilent($e);
        } elseif ($this->isCli()) {
            $this->renderCli($e);
        } elseif (!$wantsHtmlOutput) {
            if ($e instanceof \ErrorException && !$this->isFatal($e->getSeverity())) {
                if ($bufferOutput) {
                    ob_end_clean();
                }
                return;
            }

            $this->renderSilent($e);
        } else {
            $this->renderHtml($e);
        }

        if ($bufferOutput) {
            $buffer = ob_get_clean();
            if ($buffer !== false && $buffer !== '') {
                self::$deferredOutput[] = $buffer;
            }
        }
    }

    private function renderSilent(\Throwable $e): void
    {
        if ($this->isCli()) {
            fwrite(STDERR, 'An error occurred. Enable PrettyErrorHandler display option for details.' . PHP_EOL);
            return;
        }

        // Avoid leaking exception details when display is disabled.
        echo 'Internal Server Error';
    }

    private function logThrowable(\Throwable $e): void
    {
        $path = $this->resolveLogFile();
        if ($path === null) {
            return;
        }

        $type = $e instanceof \ErrorException ? $this->errorLevelToString($e->getSeverity()) : get_class($e);
        [$file, $line] = $this->resolveDisplayFrame($e);
        $timestamp = date('c');

        $message = sprintf('[%s] %s: %s in %s:%d', $timestamp, $type, $e->getMessage(), $file, $line);
        $trace = $this->formatTraceText($e);
        if ($trace !== '') {
            $message .= PHP_EOL . $trace;
        }

        $message .= str_repeat(PHP_EOL, 2);

        // Suppress warnings to avoid re-entrancy into the handler when writing fails.
        @file_put_contents($path, $message, FILE_APPEND);
    }

    private function resolveLogFile(): ?string
    {
        $cwd = getcwd();
        if (is_string($cwd) && $cwd !== '') {
            return rtrim($cwd, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'pretty_errors.txt';
        }

        $tmp = sys_get_temp_dir();
        if (!is_string($tmp) || $tmp === '') {
            return null;
        }

        return rtrim($tmp, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'pretty_errors.txt';
    }

    private function renderCli(\Throwable $e): void
    {
        // Avoid nested output if headers already started for some reason in CLI
        $type = $e instanceof \ErrorException ? $this->errorLevelToString($e->getSeverity()) : get_class($e);
        [$file, $line] = $this->resolveDisplayFrame($e);

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
        $type = $e instanceof \ErrorException ? $this->errorLevelToString($e->getSeverity()) : get_class($e);
        [$file, $line] = $this->resolveDisplayFrame($e);
        $messageRaw = $e->getMessage();
        $message = htmlspecialchars($messageRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $snippet = $this->getCodeSnippet($file, $line, (int)$this->options['context_before'], (int)$this->options['context_after']);
        $traceHtml = $this->options['show_trace'] ? $this->formatTraceHtml($e) : '';
        $traceText = $this->options['show_trace'] ? $this->formatTraceText($e) : '';
        $traceSummary = $this->options['show_trace'] ? $this->getTraceSummaryLine($e) : null;

        if (!empty($this->options['overlay'])) {
            // Render as an overlay that can sit on top of any existing page content
            $this->renderHtmlOverlay($e, $type, $message, $file, $line, $snippet, $traceHtml, $traceText, $traceSummary, $messageRaw);
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
            . '.trace{padding:16px 20px;color:#c9d1d9;font-size:13px;line-height:1.5;}'
            . '.trace .item{border-top:1px solid #30363d;padding:8px 12px;}'
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
     * @param \Throwable $e
     * @param string $type
     * @param string $message (already HTML-escaped)
     * @param string $file
     * @param int    $line
     * @param array<int, array{0:int,1:string,2:bool}>|null $snippet
     * @param string $traceHtml (already HTML-escaped markup)
     * @param string $traceText (plain-text trace)
     * @param string|null $traceSummary (plain-text single-line summary)
     * @param string $rawMessage (plain-text error message)
     */
    private function renderHtmlOverlay(\Throwable $e, string $type, string $message, string $file, int $line, ?array $snippet, string $traceHtml, string $traceText, ?string $traceSummary, string $rawMessage): void
    {
        $instance = ++self::$overlayCounter;
        $overlayId = 'php-error-overlay-' . $instance;
        $titleId = 'php-error-title-' . $instance;
        $copyButtonId = 'php-error-copy-' . $instance;
        $closeButtonId = 'php-error-close-' . $instance;
        $styleId = 'php-error-overlay-style-' . $instance;

        $fileHtml = htmlspecialchars($file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lineHtml = (string)(int)$line;
        $copyTextParts = [
            sprintf('%s: %s', $type, $rawMessage),
            sprintf('File: %s • Line: %d', $file, $line),
        ];

        $copyText = implode(PHP_EOL, $copyTextParts);

        $copyFrame = $this->resolveCopyFrame($e, $traceSummary, $file, $line);

        if ($copyFrame !== '') {
            $copyText .= PHP_EOL . $copyFrame;
        }

        // Styles scoped with `eh-` prefix to avoid collisions; very high z-index.
        echo '<style id="' . $styleId . '">'
            . '.eh-overlay{position:fixed;inset:0;display:flex;align-items:flex-start;justify-content:center;padding:32px;z-index:2147483647;background:rgba(10,15,20,.6);backdrop-filter:blur(2px);} '
            . '.eh-modal{width:min(1000px,92vw);margin-top:24px;background:#161b22;border:1px solid #30363d;border-radius:8px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.4);color:#e6edf3;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Ubuntu,Cantarell,\"Helvetica Neue\",Arial,\"Apple Color Emoji\",\"Segoe UI Emoji\";} '
            . '.eh-bar{background:#da3633;color:#fff;padding:12px 16px;font-size:16px;font-weight:600;display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;} '
            . '.eh-title{margin-right:auto;white-space:normal;overflow-wrap:anywhere;min-width:0;} '
            . '.eh-actions{display:flex;gap:8px;} '
            . '.eh-button{appearance:none;border:1px solid rgba(255,255,255,.18);background:rgba(0,0,0,.18);color:#fff;font-size:13px;line-height:1;border-radius:6px;padding:8px 12px;cursor:pointer;} '
            . '.eh-button:hover{background:rgba(255,255,255,.12);} '
            . '.eh-close{font-size:22px;padding:2px 8px;line-height:1;border:0;background:#0000;} '
            . '.eh-copy{display:flex;align-items:center;justify-content:center;gap:6px;font-weight:500;width:120px;white-space:nowrap;} '
            . '.eh-copy svg{width:16px;height:16px;fill:currentColor;} '
            . '.eh-meta{padding:12px 20px;border-bottom:1px solid #30363d;color:#8b949e;font-size:13px;} '
            . '.eh-code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,\"Liberation Mono\",\"Courier New\",monospace;font-size:13px;padding:8px 0;} '
            . '.eh-code pre{margin:0;white-space:pre;overflow:auto;background:#0b1021;color:#e6edf3;padding:12px 0;} '
            . '.eh-row{display:flex;} '
            . '.eh-ln{user-select:none;padding:0 12px 0 16px;background:#0b1021;color:#8b949e;text-align:right;border-right:1px solid #30363d;min-width:64px;} '
            . '.eh-src{padding:0 16px;white-space:pre;overflow:auto;flex:1;} '
            . '.eh-hl{background:#3a1d1d;} '
            . '.eh-trace{padding:16px 20px;color:#c9d1d9;font-size:13px;line-height:1.5;} '
            . '.eh-trace .eh-item{border-top:1px solid #30363d;padding:8px 12px;} '
            . '.eh-trace .eh-item:first-child{border-top:none;} '
            . '.trace{padding:16px 20px;color:#c9d1d9;font-size:13px;line-height:1.5;} '
            . '.trace .item{border-top:1px solid #30363d;padding:8px 12px;} '
            . '.trace .item:first-child{border-top:none;} '
            . '.eh-small{opacity:.85;font-size:12px;} '
            . '</style>';

        echo '<div class="eh-overlay" id="' . $overlayId . '" role="dialog" aria-modal="true" aria-labelledby="' . $titleId . '">'
            . '<div class="eh-modal">'
            . '<div class="eh-bar">'
            . '<div class="eh-title" id="' . $titleId . '">' . htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ': ' . $message . '</div>'
            . '<div class="eh-actions">'
            . '<button type="button" class="eh-button eh-copy" id="' . $copyButtonId . '" aria-label="Copy error"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 18H8V7h11v16z"/></svg>Copy error</button>'
            . '<button type="button" class="eh-button eh-close" id="' . $closeButtonId . '" aria-label="Close">&times;</button>'
            . '</div>'
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

        $script = '<script>(function(){try{var ov=document.getElementById("' . $overlayId . '");if(!ov)return;var closeBtn=document.getElementById("' . $closeButtonId . '");var modal=ov.querySelector(".eh-modal");var copyBtn=document.getElementById("' . $copyButtonId . '");var originalCopyLabel=copyBtn?copyBtn.textContent:"";var copyText=' . json_encode($copyText) . ';function close(){if(ov&&ov.parentNode){ov.parentNode.removeChild(ov);}document.removeEventListener("keydown",onKey);if(ov){ov.removeEventListener("click",onOverlayClick);}}function onKey(e){if(e.key==="Escape"){close();}}function onOverlayClick(e){if(!modal||modal.contains(e.target)){return;}close();}if(closeBtn){closeBtn.addEventListener("click",close);}document.addEventListener("keydown",onKey);ov.addEventListener("click",onOverlayClick);if(copyBtn){copyBtn.addEventListener("click",function(){var reset=function(){copyBtn.textContent=originalCopyLabel;};var markCopied=function(){copyBtn.textContent="Copied!";setTimeout(reset,1500);};var fallback=function(){var textarea=document.createElement("textarea");textarea.value=copyText;textarea.setAttribute("readonly","true");textarea.style.position="fixed";textarea.style.opacity="0";document.body.appendChild(textarea);if(typeof textarea.focus==="function"){textarea.focus();}textarea.select();if(typeof textarea.setSelectionRange==="function"){textarea.setSelectionRange(0,textarea.value.length);}var copied=false;try{if(typeof document.execCommand==="function"){copied=document.execCommand("copy");}}catch(err){console.error(err);}document.body.removeChild(textarea);if(copied){markCopied();}else{reset();}};if(window.navigator&&navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(copyText).then(function(){markCopied();},function(){fallback();});}else{fallback();}});} }catch(e){/* noop */}})();</script>';

        echo '</div>' . str_replace(['class="item"'], ['class="eh-item"'], $traceHtml) . '</div></div>' . $script;
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

    private function getTraceSummaryLine(\Throwable $e): ?string
    {
        $trace = $e->getTrace();
        for ($i = count($trace) - 1; $i >= 0; $i--) {
            $frame = $trace[$i];
            $class = $frame['class'] ?? '';
            if ($class === self::class) {
                continue;
            }
            $func = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
            $file = $frame['file'] ?? '[internal]';
            $line = isset($frame['line']) ? (int)$frame['line'] : null;
            return $this->formatCopyFrame($file, $line, $func);
        }

        return null;
    }

    /**
     * Determine the frame string to place on the clipboard.
     */
    private function resolveCopyFrame(\Throwable $e, ?string $traceSummary, string $fallbackFile, int $fallbackLine): string
    {
        if ($traceSummary !== null && $traceSummary !== '') {
            return $traceSummary;
        }

        [$displayFile, $displayLine] = $this->resolveDisplayFrame($e);

        $trace = $e->getTrace();
        for ($i = count($trace) - 1; $i >= 0; $i--) {
            $frame = $trace[$i];
            if (!isset($frame['file'], $frame['line'])) {
                continue;
            }

            if ((string)$frame['file'] !== $displayFile || (int)$frame['line'] !== $displayLine) {
                continue;
            }

            $func = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
            return $this->formatCopyFrame($displayFile, $displayLine, $func);
        }

        return $this->formatCopyFrame($displayFile ?: $fallbackFile, $displayLine ?: $fallbackLine, null);
    }

    private function formatCopyFrame(string $file, ?int $line, ?string $func): string
    {
        $location = $line === null ? $file : $file . ':' . $line;
        $location = trim($location);
        $function = trim((string)$func);

        if ($function !== '') {
            return 'at ' . $location . ' (' . $function . ')';
        }

        return 'at ' . $location;
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

    /**
     * Determine which file/line should be highlighted in the rendered output.
     *
     * @return array{0:string,1:int}
     */
    private function resolveDisplayFrame(\Throwable $e): array
    {
        $file = $e->getFile();
        $line = $e->getLine();

        $trace = $e->getTrace();
        if ($trace) {
            foreach ($trace as $frame) {
                if (!isset($frame['file'], $frame['line'])) {
                    continue;
                }

                $frameFile = (string)$frame['file'];
                if ($frameFile === __FILE__) {
                    continue;
                }

                $file = $frameFile;
                $line = (int)$frame['line'];
                break;
            }
        }

        return [$file, $line];
    }

    private static function flushDeferredOutput(): void
    {
        if (!self::$deferredOutput) {
            return;
        }

        if (!self::shouldFlushDeferredOutput()) {
            self::$deferredOutput = [];
            return;
        }

        foreach (self::$deferredOutput as $chunk) {
            echo $chunk;
        }

        self::$deferredOutput = [];
    }

    private static function shouldFlushDeferredOutput(): bool
    {
        if (!self::$globalInstance instanceof self) {
            return false;
        }

        return self::$globalInstance->wantsHtmlOutput();
    }

    private function wantsHtmlOutput(): bool
    {
        if ($this->isCli()) {
            return false;
        }

        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $value = strtolower(trim(substr($header, strlen('Content-Type:'))));
                if ($value !== '' && !$this->isHtmlMime($value)) {
                    return false;
                }

                if ($this->isHtmlMime($value)) {
                    return true;
                }
            }
        }

        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        if ($accept === '') {
            return true;
        }

        return str_contains($accept, 'text/html') || str_contains($accept, 'application/xhtml+xml');
    }

    private function isHtmlMime(string $value): bool
    {
        return str_contains($value, 'text/html') || str_contains($value, 'application/xhtml+xml');
    }
}

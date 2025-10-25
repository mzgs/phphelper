<?php

namespace PhpHelper;
class App
{
    public static function isLocal(): bool
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        $host = strtok($host, ':') ?: $host;

        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return true;
        }

        $ips = [
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['SERVER_ADDR'] ?? null,
        ];

        foreach ($ips as $ip) {
            if (!is_string($ip) || $ip === '') {
                continue;
            }

            if ($ip === '127.0.0.1' || $ip === '::1') {
                return true;
            }
        }

        return false;
    }

    public static function isCli(): bool
    {
        return in_array(PHP_SAPI, ['cli', 'phpdbg'], true);
    }

    public static function isProduction(): bool
    {
        return !self::isLocal() && !self::isCli();
    }


    public static function cliMenu(array $options): void
    {
        if (!self::isCli()) {
            return;
        }

        if ($options === []) {
            return;
        }

        $menu = [];
        $index = 1;

        foreach ($options as $label => $commandSpec) {
            $command = null;
            $callable = null;
            $displayLabel = null;

            if (is_array($commandSpec)) {
                $value = $commandSpec['command'] ?? null;
                if (is_string($value)) {
                    $command = trim($value);
                }

                $labelValue = $commandSpec['label'] ?? $commandSpec['title'] ?? null;
                if (is_string($labelValue)) {
                    $displayLabel = trim($labelValue);
                }

                $callableValue = $commandSpec['run'] ?? $commandSpec['callback'] ?? $commandSpec['callable'] ?? null;
                if (is_callable($callableValue)) {
                    $callable = $callableValue;
                }
            } elseif (is_string($commandSpec)) {
                $command = trim($commandSpec);
            } elseif (is_callable($commandSpec)) {
                $callable = $commandSpec;
            }

            if (($command === null || $command === '') && $callable === null) {
                throw new \InvalidArgumentException('Each CLI menu option must define a non-empty command or callable.');
            }

            if ($displayLabel === null || $displayLabel === '') {
                if (is_string($label)) {
                    $displayLabel = trim($label);
                }

                if ($displayLabel === null || $displayLabel === '') {
                    $displayLabel = $command !== null && $command !== '' ? $command : 'Option ' . $index;
                }
            }

            $menu[$index] = [
                'label' => $displayLabel,
                'command' => $command,
                'callable' => $callable,
            ];

            ++$index;
        }

        if ($menu === []) {
            return;
        }

        $closeStdout = false;
        if (defined('STDOUT') && is_resource(STDOUT)) {
            $stdout = STDOUT;
        } else {
            $stdout = fopen('php://stdout', 'w');
            $closeStdout = true;
        }

        if (!is_resource($stdout)) {
            throw new \RuntimeException('Unable to access CLI output.');
        }

        $closeStdin = false;
        if (defined('STDIN') && is_resource(STDIN)) {
            $stdin = STDIN;
        } else {
            $stdin = fopen('php://stdin', 'r');
            $closeStdin = true;
        }

        if (!is_resource($stdin)) {
            if ($closeStdout) {
                fclose($stdout);
            }

            throw new \RuntimeException('Unable to access CLI input.');
        }

        $write = static function (string $message) use ($stdout): void {
            fwrite($stdout, $message);
        };

        $autoChoice = null;
        if (isset($GLOBALS['argv']) && is_array($GLOBALS['argv']) && count($GLOBALS['argv']) > 1) {
            foreach (array_slice($GLOBALS['argv'], 1) as $argument) {
                $candidate = trim((string) $argument);
                if ($candidate !== '') {
                    $autoChoice = $candidate;
                    break;
                }
            }
        }

        try {
            while (true) {
                $fromAuto = false;

                if ($autoChoice !== null) {
                    $choice = $autoChoice;
                    $autoChoice = null;
                    $fromAuto = true;
                } else {
                    $write(PHP_EOL . '=== Command Menu ===' . PHP_EOL);

                    foreach ($menu as $number => $item) {
                        $write($number . ') ' . $item['label'] . PHP_EOL);
                    }

                    $write('Choose an option: ');

                    $input = fgets($stdin);
                    if ($input === false) {
                        $write(PHP_EOL . 'No input detected. Exiting.' . PHP_EOL);

                        return;
                    }

                    $choice = trim($input);
                    if ($choice === '') {
                        continue;
                    }
                }

                if (!ctype_digit($choice)) {
                    if ($fromAuto) {
                        $write('Invalid selection provided via CLI arguments.' . PHP_EOL);

                        exit(1);
                    }

                    $write('Invalid selection. Enter the option number and press Enter.' . PHP_EOL);

                    continue;
                }

                $selected = (int) $choice;
                if (!isset($menu[$selected])) {
                    if ($fromAuto) {
                        $write('Unknown option provided via CLI arguments.' . PHP_EOL);

                        exit(1);
                    }

                    $write('Unknown option. Please choose a valid number.' . PHP_EOL);

                    continue;
                }

                $entry = $menu[$selected];
                $command = $entry['command'];
                $callable = $entry['callable'];

                if ($command !== null && $command !== '') {
                    $write(PHP_EOL . '$ ' . $command . PHP_EOL);

                    $bashCommand = 'bash -lc ' . escapeshellarg($command);
                    $exitCode = 0;

                    if (function_exists('passthru')) {
                        passthru($bashCommand, $exitCode);
                    } else {
                        system($bashCommand, $exitCode);
                    }

                    $write(PHP_EOL . 'Exit code: ' . $exitCode . PHP_EOL);

                    exit($exitCode);
                }

                if ($callable === null) {
                    $write('Invalid configuration for selected option.' . PHP_EOL);

                    continue;
                }

                $exitCode = 0;
                try {
                    $result = $callable();
                    if (is_int($result)) {
                        $exitCode = $result;
                    }
                } catch (\Throwable $throwable) {
                    $write('Error: ' . $throwable->getMessage() . PHP_EOL);
                    $code = (int) $throwable->getCode();
                    $exitCode = $code !== 0 ? $code : 1;
                }

                $write(PHP_EOL . 'Exit code: ' . $exitCode . PHP_EOL);

                exit($exitCode);
            }
        } finally {
            if ($closeStdin && is_resource($stdin)) {
                fclose($stdin);
            }

            if ($closeStdout && is_resource($stdout)) {
                fclose($stdout);
            }
        }

        exit(0);
    }

}

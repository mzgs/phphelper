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
            } elseif (is_string($commandSpec)) {
                $command = trim($commandSpec);
            }

            if ($command === null || $command === '') {
                throw new \InvalidArgumentException('Each CLI menu option must define a non-empty command.');
            }

            if ($displayLabel === null || $displayLabel === '') {
                if (is_string($label)) {
                    $displayLabel = trim($label);
                }

                if ($displayLabel === null || $displayLabel === '') {
                    $displayLabel = $command;
                }
            }

            $menu[$index] = [
                'label' => $displayLabel,
                'command' => $command,
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

        try {
            while (true) {
                $write(PHP_EOL . '=== Command Menu ===' . PHP_EOL);

                foreach ($menu as $number => $item) {
                    $write($number . ') ' . $item['label'] . PHP_EOL);
                }

                $write('q) Quit' . PHP_EOL);
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

                $choiceLower = strtolower($choice);
                if (in_array($choiceLower, ['q', 'quit', 'exit'], true)) {
                    $write('Goodbye!' . PHP_EOL);

                    return;
                }

                if (!ctype_digit($choice)) {
                    $write('Invalid selection. Enter the option number and press Enter.' . PHP_EOL);

                    continue;
                }

                $selected = (int) $choice;
                if (!isset($menu[$selected])) {
                    $write('Unknown option. Please choose a valid number.' . PHP_EOL);

                    continue;
                }

                $command = $menu[$selected]['command'];
                $write(PHP_EOL . '$ ' . $command . PHP_EOL);

                $bashCommand = 'bash -lc ' . escapeshellarg($command);
                $exitCode = 0;

                if (function_exists('passthru')) {
                    passthru($bashCommand, $exitCode);
                } else {
                    system($bashCommand, $exitCode);
                }

                $write(PHP_EOL . 'Exit code: ' . $exitCode . PHP_EOL);
            }
        } finally {
            if ($closeStdin && is_resource($stdin)) {
                fclose($stdin);
            }

            if ($closeStdout && is_resource($stdout)) {
                fclose($stdout);
            }
        }
    }

}

<?php

class TwigHelper
{
    private static ?\Twig\Environment $environment = null;

    /**
     * Bootstrap a Twig environment using a filesystem loader and remember it for later use.
     *
     * @param string|array<int|string, string|array<int, string>> $paths Template directories keyed by namespace or default when numeric
     * @param array<string, mixed> $options Options forwarded to \Twig\Environment (cache, auto_reload, strict_variables, ...)
     */
    public static function init(string|array $paths = [], array $options = [], ?\Twig\Loader\LoaderInterface $loader = null): \Twig\Environment
    {
        self::assertTwigAvailable();

        if ($loader === null) {
            $loader = self::createLoader($paths);
        } elseif (!empty($paths)) {
            if ($loader instanceof \Twig\Loader\FilesystemLoader) {
                self::registerPaths($loader, $paths);
            } else {
                throw new InvalidArgumentException('Cannot register template paths on the provided Twig loader instance.');
            }
        }

        $environment = new \Twig\Environment($loader, array_merge(self::defaultOptions(), $options));
        self::$environment = $environment;
        return $environment;
    }

    /**
     * Store an existing Twig environment for helpers to use.
     */
    public static function setEnvironment(\Twig\Environment $environment): void
    {
        self::$environment = $environment;
    }

    /**
     * Check whether an environment was initialized.
     */
    public static function hasEnvironment(): bool
    {
        return self::$environment instanceof \Twig\Environment;
    }

    /**
     * Get the stored environment or throw if none is configured.
     */
    public static function env(): \Twig\Environment
    {
        if (!self::hasEnvironment()) {
            throw new RuntimeException('TwigHelper environment is not initialized. Call TwigHelper::init() or ::setEnvironment() first.');
        }
        return self::$environment;
    }

    /**
     * Render a template using the stored environment (or a provided one).
     *
     * @param array<string, mixed> $context
     */
    public static function render(string $template, array $context = [], ?\Twig\Environment $environment = null): string
    {
        $env = self::resolveEnvironment($environment);
        return $env->render($template, $context);
    }

    /**
     * Register a global variable available in every template.
     */
    public static function addGlobal(string $name, mixed $value, ?\Twig\Environment $environment = null): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('Global name must be a non-empty string.');
        }
        $env = self::resolveEnvironment($environment);
        $env->addGlobal($name, $value);
    }

    /**
     * Register a reusable Twig function.
     *
     * @param array<string, mixed> $options Options forwarded to \Twig\TwigFunction (needs_context, needs_environment, is_safe, ...)
     */
    public static function addFunction(string $name, callable $callable, array $options = [], ?\Twig\Environment $environment = null): void
    {
        self::assertTwigAvailable(\Twig\TwigFunction::class);
        $env = self::resolveEnvironment($environment);
        $env->addFunction(new \Twig\TwigFunction($name, $callable, $options));
    }

    /**
     * Register a reusable Twig filter.
     *
     * @param array<string, mixed> $options Options forwarded to \Twig\TwigFilter (needs_context, needs_environment, is_safe, ...)
     */
    public static function addFilter(string $name, callable $callable, array $options = [], ?\Twig\Environment $environment = null): void
    {
        self::assertTwigAvailable(\Twig\TwigFilter::class);
        $env = self::resolveEnvironment($environment);
        $env->addFilter(new \Twig\TwigFilter($name, $callable, $options));
    }

    /**
     * Add an extra template directory to the current filesystem loader.
     */
    public static function addPath(string $path, ?string $namespace = null, ?\Twig\Environment $environment = null): void
    {
        $env = self::resolveEnvironment($environment);
        $loader = $env->getLoader();
        if (!$loader instanceof \Twig\Loader\FilesystemLoader) {
            throw new RuntimeException('The current Twig loader does not support filesystem paths.');
        }

        $normalised = self::normaliseDirectory($path);
        if ($namespace === null || $namespace === '') {
            $loader->addPath($normalised);
        } else {
            $loader->addPath($normalised, $namespace);
        }
    }

    /**
     * Forget any stored environment (useful for tests).
     */
    public static function clear(): void
    {
        self::$environment = null;
    }

    private static function resolveEnvironment(?\Twig\Environment $environment): \Twig\Environment
    {
        if ($environment instanceof \Twig\Environment) {
            return $environment;
        }
        return self::env();
    }

    /**
     * @return \Twig\Loader\FilesystemLoader
     */
    private static function createLoader(string|array $paths): \Twig\Loader\FilesystemLoader
    {
        self::assertTwigAvailable(\Twig\Loader\FilesystemLoader::class);
        $loader = new \Twig\Loader\FilesystemLoader();
        self::registerPaths($loader, $paths);
        return $loader;
    }

    /**
     * @param string|array<int|string, string|array<int, string>> $paths
     */
    private static function registerPaths(\Twig\Loader\FilesystemLoader $loader, string|array $paths): void
    {
        foreach (self::normalisePaths($paths) as $definition) {
            if ($definition['namespace'] === null) {
                $loader->addPath($definition['path']);
            } else {
                $loader->addPath($definition['path'], $definition['namespace']);
            }
        }
    }

    /**
     * @param string|array<int|string, string|array<int, string>> $paths
     * @return array<int, array{path: string, namespace: ?string}>
     */
    private static function normalisePaths(string|array $paths): array
    {
        if ($paths === '' || $paths === []) {
            return [];
        }

        $paths = is_string($paths) ? [$paths] : $paths;
        $definitions = [];

        foreach ($paths as $namespace => $value) {
            $ns = is_int($namespace) ? null : (string)$namespace;
            if ($value === [] || $value === '') {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    $definitions[] = [
                        'path' => self::normaliseDirectory($item),
                        'namespace' => $ns === '' ? null : $ns,
                    ];
                }
            } else {
                $definitions[] = [
                    'path' => self::normaliseDirectory($value),
                    'namespace' => $ns === '' ? null : $ns,
                ];
            }
        }

        return $definitions;
    }

    private static function normaliseDirectory(mixed $path): string
    {
        if (!is_string($path) || trim($path) === '') {
            throw new InvalidArgumentException('Twig template paths must be non-empty strings.');
        }

        $clean = rtrim($path, '\\/');
        if (!is_dir($clean)) {
            throw new InvalidArgumentException(sprintf('Twig template directory "%s" does not exist.', $path));
        }

        $real = realpath($clean);
        return $real !== false ? $real : $clean;
    }

    /**
     * Default environment options applied when initialising Twig.
     *
     * @return array<string, mixed>
     */
    private static function defaultOptions(): array
    {
        return [
            'cache' => false,
            'auto_reload' => true,
            'strict_variables' => false,
        ];
    }

    private static function assertTwigAvailable(string $class = \Twig\Environment::class): void
    {
        if (!class_exists($class)) {
            throw new RuntimeException('Twig is not available. Install it via "composer require twig/twig".');
        }
    }
}

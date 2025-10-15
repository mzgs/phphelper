<?php

class TwigHelper
{
    private static ?\Twig\Environment $environment = null;
    /** @var ?\SplObjectStorage<\Twig\Environment, bool> */
    private static ?\SplObjectStorage $bootstrapped = null;

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
        self::registerDefaults($environment);
        return $environment;
    }

    /**
     * Store an existing Twig environment for helpers to use.
     */
    public static function setEnvironment(\Twig\Environment $environment): void
    {
        self::$environment = $environment;
        self::registerDefaults($environment);
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
     * Register the common helper filters and functions provided by this library.
     */
    public static function registerDefaults(?\Twig\Environment $environment = null): void
    {
        $env = $environment instanceof \Twig\Environment
            ? $environment
            : self::resolveEnvironment($environment);

        if (self::$bootstrapped === null) {
            self::$bootstrapped = new \SplObjectStorage();
        }

        if (self::$bootstrapped->contains($env)) {
            return;
        }

        self::registerDefaultFilters($env);
        self::registerDefaultFunctions($env);
        self::$bootstrapped->attach($env, true);
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

    private static function registerDefaultFilters(\Twig\Environment $environment): void
    {
        foreach (self::defaultFilters() as $definition) {
            if (!self::isCallableAvailable($definition['callable'])) {
                continue;
            }
            $environment->addFilter(new \Twig\TwigFilter(
                $definition['name'],
                $definition['callable'],
                $definition['options']
            ));
        }
    }

    private static function registerDefaultFunctions(\Twig\Environment $environment): void
    {
        foreach (self::defaultFunctions() as $definition) {
            if (!self::isCallableAvailable($definition['callable'])) {
                continue;
            }
            $environment->addFunction(new \Twig\TwigFunction(
                $definition['name'],
                $definition['callable'],
                $definition['options']
            ));
        }
    }

    /**
     * @return array<int, array{name: string, callable: callable|array|string, options: array<string, mixed>}> 
     */
    private static function defaultFilters(): array
    {
        return [
            ['name' => 'bytes', 'callable' => [Format::class, 'bytes'], 'options' => []],
            ['name' => 'currency', 'callable' => [Format::class, 'currency'], 'options' => []],
            ['name' => 'number', 'callable' => [Format::class, 'number'], 'options' => []],
            ['name' => 'percent', 'callable' => [Format::class, 'percent'], 'options' => []],
            ['name' => 'short_number', 'callable' => [Format::class, 'shortNumber'], 'options' => []],
            ['name' => 'duration', 'callable' => [Format::class, 'duration'], 'options' => []],
            ['name' => 'hms', 'callable' => [Format::class, 'hms'], 'options' => []],
            ['name' => 'ordinal', 'callable' => [Format::class, 'ordinal'], 'options' => []],
            ['name' => 'bool_label', 'callable' => [Format::class, 'bool'], 'options' => []],
            ['name' => 'json_pretty', 'callable' => [Format::class, 'json'], 'options' => []],
            ['name' => 'ago', 'callable' => [Date::class, 'ago'], 'options' => []],
            ['name' => 'slug', 'callable' => [Str::class, 'slug'], 'options' => []],
            ['name' => 'camel', 'callable' => [Str::class, 'camel'], 'options' => []],
            ['name' => 'snake', 'callable' => [Str::class, 'snake'], 'options' => []],
            ['name' => 'studly', 'callable' => [Str::class, 'studly'], 'options' => []],
            ['name' => 'titlecase', 'callable' => [Str::class, 'title'], 'options' => []],
            ['name' => 'lowercase', 'callable' => [Str::class, 'lower'], 'options' => []],
            ['name' => 'uppercase', 'callable' => [Str::class, 'upper'], 'options' => []],
            ['name' => 'limit', 'callable' => [Str::class, 'limit'], 'options' => []],
            ['name' => 'words', 'callable' => [Str::class, 'words'], 'options' => []],
            ['name' => 'squish', 'callable' => [Str::class, 'squish'], 'options' => []],
            ['name' => 'seo_filename', 'callable' => [Str::class, 'seoFileName'], 'options' => []],
            ['name' => 'seo_url', 'callable' => [Str::class, 'seoUrl'], 'options' => []],
        ];
    }

    /**
     * @return array<int, array{name: string, callable: callable|array|string, options: array<string, mixed>}> 
     */
    private static function defaultFunctions(): array
    {
        return [
            ['name' => 'format_date', 'callable' => [Date::class, 'format'], 'options' => []],
            ['name' => 'date_timestamp', 'callable' => [Date::class, 'timestamp'], 'options' => []],
            ['name' => 'array_get', 'callable' => [Arrays::class, 'get'], 'options' => []],
            ['name' => 'array_has', 'callable' => [Arrays::class, 'has'], 'options' => []],
            ['name' => 'array_first', 'callable' => [Arrays::class, 'first'], 'options' => []],
            ['name' => 'array_last', 'callable' => [Arrays::class, 'last'], 'options' => []],
            ['name' => 'format_bytes', 'callable' => [Format::class, 'bytes'], 'options' => []],
            ['name' => 'format_currency', 'callable' => [Format::class, 'currency'], 'options' => []],
            ['name' => 'format_number', 'callable' => [Format::class, 'number'], 'options' => []],
            ['name' => 'format_percent', 'callable' => [Format::class, 'percent'], 'options' => []],
            ['name' => 'format_short_number', 'callable' => [Format::class, 'shortNumber'], 'options' => []],
        ];
    }

    private static function isCallableAvailable(callable|array|string $callable): bool
    {
        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            self::ensureHelper($callable[0]);
        } elseif (is_string($callable) && str_contains($callable, '::')) {
            [$class] = explode('::', $callable, 2);
            self::ensureHelper($class);
        }

        return is_callable($callable);
    }

    private static function ensureHelper(string $class): void
    {
        if (class_exists($class, false)) {
            return;
        }

        $file = __DIR__ . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
}

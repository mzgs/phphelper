<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../src/TwigHelper.php';

final class TwigHelperTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        TwigHelper::clear();
        $this->workspace = sys_get_temp_dir() . '/twighelper_' . uniqid('', true);
        if (!mkdir($concurrentDirectory = $this->workspace, 0777, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
        TwigHelper::clear();
        parent::tearDown();
    }

    public function testInitRenderAndGlobalAcrossNamespaces(): void
    {
        $defaultDir = $this->createTemplateDirectory('views', [
            'greeting.twig' => 'Hello {{ name }} {{ app_name }}',
        ]);
        $emailsDir = $this->createTemplateDirectory('emails', [
            'reminder.twig' => 'Reminder for {{ user }} via {{ app_name }}',
        ]);

        TwigHelper::init([$defaultDir, 'emails' => $emailsDir], ['strict_variables' => true]);
        TwigHelper::addGlobal('app_name', 'HelperApp');

        $this->assertSame('Hello Ada HelperApp', TwigHelper::render('greeting.twig', ['name' => 'Ada']));
        $this->assertSame(
            'Reminder for Bob via HelperApp',
            TwigHelper::render('@emails/reminder.twig', ['user' => 'Bob'])
        );
    }

    public function testAddFunctionAndFilter(): void
    {
        $dir = $this->createTemplateDirectory('functions', [
            'macros.twig' => '{{ shout(name) }} {{ value|double }}',
        ]);

        TwigHelper::init($dir);
        TwigHelper::addFunction('shout', static fn (string $value): string => strtoupper($value));
        TwigHelper::addFilter('double', static fn (int $value): int => $value * 2);

        $this->assertSame('HELLO 8', TwigHelper::render('macros.twig', ['name' => 'hello', 'value' => 4]));
    }

    public function testDefaultFiltersAndFunctionsAreRegistered(): void
    {
        $dir = $this->createTemplateDirectory('helpers', [
            'format.twig' => '{{ "Hello World"|slug }}|{{ 2048|bytes }}|{{ 1234|short_number }}|{{ 1|ordinal }}|{{ format_date(date_value, "date") }}|{{ array_get(user, "profile.name") }}',
        ]);

        TwigHelper::init($dir);

        $output = TwigHelper::render('format.twig', [
            'date_value' => '2024-01-05 10:20:30',
            'user' => ['profile' => ['name' => 'Ada']],
        ]);

        $this->assertSame('hello-world|2 KB|1.2K|1st|2024-01-05|Ada', $output);
    }

    public function testSetEnvironmentUsesProvidedInstance(): void
    {
        $dir = $this->createTemplateDirectory('custom', [
            'index.twig' => 'Hi {{ name }}',
        ]);

        $loader = new FilesystemLoader($dir);
        $environment = new Environment($loader, ['cache' => false]);
        TwigHelper::setEnvironment($environment);

        $this->assertTrue(TwigHelper::hasEnvironment());
        $this->assertSame('Hi Ada', TwigHelper::render('index.twig', ['name' => 'Ada']));
    }

    public function testEnvThrowsWhenNotInitialised(): void
    {
        TwigHelper::clear();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TwigHelper environment is not initialized');
        TwigHelper::env();
    }

    public function testInitThrowsWhenDirectoryMissing(): void
    {
        $missing = $this->workspace . '/missing';
        $this->expectException(InvalidArgumentException::class);
        TwigHelper::init($missing);
    }

    private function createTemplateDirectory(string $name, array $templates): string
    {
        $dir = $this->workspace . '/' . $name;
        if (!mkdir($concurrentDirectory = $dir, 0777, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        foreach ($templates as $file => $contents) {
            $path = $dir . '/' . $file;
            if (file_put_contents($path, $contents) === false) {
                throw new RuntimeException('Failed to write template: ' . $path);
            }
        }

        return $dir;
    }

    private function removeDirectory(?string $path): void
    {
        if ($path === null || $path === '' || !is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
            } else {
                @unlink($fullPath);
            }
        }

        @rmdir($path);
    }
}

<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpHelper\Files;

final class FilesTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir() . '/phphelper_' . uniqid('', true);
        mkdir($this->testDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->testDir);
        parent::tearDown();
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());
            } else {
                unlink($fileInfo->getPathname());
            }
        }

        rmdir($path);
    }

    private function path(string $name): string
    {
        return $this->testDir . DIRECTORY_SEPARATOR . $name;
    }

    private function createFile(string $name, string $contents = 'content'): string
    {
        $path = $this->path($name);
        Files::write($path, $contents);
        return $path;
    }

    public function testWriteCreatesParentDirectoryAndReadsBackContents(): void
    {
        $nestedPath = $this->path('nested/dir/test.txt');

        $bytes = Files::write($nestedPath, 'hello world');

        $this->assertSame(11, $bytes);
        $this->assertSame('hello world', Files::read($nestedPath));
        $this->assertFileExists($nestedPath);
    }

    public function testReadReturnsFalseWhenFileMissingOrUnreadable(): void
    {
        $missingPath = $this->path('missing.txt');
        $this->assertFalse(Files::read($missingPath));
    }

    public function testAppendAddsContentToExistingFile(): void
    {
        $file = $this->createFile('append.txt', 'first');

        Files::append($file, ' second');

        $this->assertSame('first second', Files::read($file));
    }

    public function testDeleteRemovesExistingFile(): void
    {
        $file = $this->createFile('delete.txt');

        $this->assertTrue(Files::delete($file));
        $this->assertFileDoesNotExist($file);
        $this->assertFalse(Files::delete($file));
    }

    public function testCopyCreatesDestinationDirectory(): void
    {
        $source = $this->createFile('source.txt', 'copy me');
        $destination = $this->path('copy/target.txt');

        $this->assertTrue(Files::copy($source, $destination));
        $this->assertFileExists($destination);
        $this->assertSame('copy me', Files::read($destination));
    }

    public function testMoveRelocatesFile(): void
    {
        $source = $this->createFile('move.txt', 'move me');
        $destination = $this->path('moved/target.txt');

        $this->assertTrue(Files::move($source, $destination));
        $this->assertFileExists($destination);
        $this->assertFileDoesNotExist($source);
        $this->assertSame('move me', Files::read($destination));
    }

    public function testExistsChecksForRegularFile(): void
    {
        $file = $this->createFile('exists.txt');
        $directory = $this->path('directory');
        mkdir($directory);

        $this->assertTrue(Files::exists($file));
        $this->assertFalse(Files::exists($directory));
        $this->assertFalse(Files::exists($this->path('missing.txt')));
    }

    public function testSize(): void
    {
        $content = str_repeat('a', 2048);
        $file = $this->createFile('size.txt', $content);

        $this->assertSame(2048, Files::size($file));
        $this->assertFalse(Files::size($this->path('missing.txt')));
    }

    public function testExtensionReturnsLowercaseExtension(): void
    {
        $this->assertSame('txt', Files::extension('/foo/BAR.Txt'));
        $this->assertSame('gitignore', Files::extension('/path/.gitignore'));
    }

    public function testMimeTypeForExistingFile(): void
    {
        $file = $this->createFile('mime.txt', 'plain text');

        $this->assertNotFalse(Files::mimeType($file));
        $this->assertStringStartsWith('text/plain', (string) Files::mimeType($file));
        $this->assertFalse(Files::mimeType($this->path('missing.txt')));
    }

    public function testModifiedTimeReturnsTimestamp(): void
    {
        $file = $this->createFile('time.txt');

        $modified = Files::modifiedTime($file);

        $this->assertIsInt($modified);
        $this->assertGreaterThan(time() - 5, $modified);
        $this->assertFalse(Files::modifiedTime($this->path('missing.txt')));
    }

    public function testCreateDirectorySucceedsForExistingAndNewPaths(): void
    {
        $path = $this->path('new/directory');

        $this->assertTrue(Files::createDirectory($path));
        $this->assertDirectoryExists($path);
        $this->assertTrue(Files::createDirectory($path));
    }

    public function testDeleteDirectoryRemovesNestedStructure(): void
    {
        $path = $this->path('nestedDir');
        $nestedFile = $path . DIRECTORY_SEPARATOR . 'child' . DIRECTORY_SEPARATOR . 'file.txt';
        Files::write($nestedFile, 'data');

        $this->assertTrue(Files::deleteDirectory($path));
        $this->assertDirectoryDoesNotExist($path);
        $this->assertFalse(Files::deleteDirectory($path));
    }

    public function testListFilesHonorsPattern(): void
    {
        $this->createFile('alpha.txt');
        $this->createFile('beta.log');
        $this->createFile('gamma.txt');
        mkdir($this->path('directory'));

        $allFiles = Files::listFiles($this->testDir);
        $txtFiles = Files::listFiles($this->testDir, '*.txt');
        $missing = Files::listFiles($this->path('missing'));

        $this->assertEqualsCanonicalizing([
            $this->path('alpha.txt'),
            $this->path('beta.log'),
            $this->path('gamma.txt'),
        ], $allFiles);
        $this->assertEqualsCanonicalizing([
            $this->path('alpha.txt'),
            $this->path('gamma.txt'),
        ], $txtFiles);
        $this->assertSame([], $missing);
    }

    public function testListDirectoriesReturnsOnlyDirectories(): void
    {
        mkdir($this->path('dirA'));
        mkdir($this->path('dirB'));
        $this->createFile('file.txt');

        $dirs = Files::listDirectories($this->testDir);
        $missing = Files::listDirectories($this->path('missing'));

        $this->assertEqualsCanonicalizing([
            $this->path('dirA'),
            $this->path('dirB'),
        ], $dirs);
        $this->assertSame([], $missing);
    }

    public function testReadAndWriteJson(): void
    {
        $data = ['name' => 'php', 'version' => '8.3'];
        $path = $this->path('data.json');
        $resource = tmpfile();

        $this->assertSame(strlen(json_encode($data, JSON_PRETTY_PRINT)), Files::writeJson($path, $data));
        $this->assertSame($data, Files::readJson($path));
        $invalidPath = $this->path('invalid.json');
        $this->assertFalse(Files::writeJson($invalidPath, $resource));
        $this->assertFileDoesNotExist($invalidPath);
        $this->assertFalse(Files::readJson($this->path('missing.json')));

        fclose($resource);
    }

    public function testReadAndWriteCsv(): void
    {
        $rows = [
            ['id', 'name'],
            ['1', 'alpha'],
            ['2', 'beta'],
        ];
        $path = $this->path('data.csv');

        $this->assertTrue(Files::writeCsv($path, $rows));
        $this->assertSame($rows, Files::readCsv($path));
        $this->assertFalse(Files::readCsv($this->path('missing.csv')));
    }

    public function testHashCalculatesFileDigest(): void
    {
        $file = $this->createFile('hash.txt', 'hash me');

        $this->assertSame(hash_file('sha256', $file), Files::hash($file));
        $this->assertSame(hash_file('md5', $file), Files::hash($file, 'md5'));
        $this->assertFalse(Files::hash($this->path('missing.txt')));
    }

    public function testIsAbsoluteDetectsUnixAndWindowsPaths(): void
    {
        $this->assertTrue(Files::isAbsolute('/usr/bin')); // Unix style
        $this->assertTrue(Files::isAbsolute('C:\\Windows'));
        $this->assertFalse(Files::isAbsolute('relative/path'));
    }

    public function testNormalizePathReplacesSeparators(): void
    {
        $mixed = 'folder\\sub/child';
        $expected = 'folder' . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'child';

        $this->assertSame($expected, Files::normalizePath($mixed));
    }

    public function testJoinPathsTrimsTrailingSeparators(): void
    {
        $joined = Files::joinPaths('var/', 'log', 'app');
        $expected = 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'app';

        $this->assertSame($expected, $joined);
    }

    public function testSizeForEmptyFile(): void
    {
        $file = $this->createFile('empty.txt', '');

        $this->assertSame(0, Files::size($file));
    }

    public function testExtensionWithoutDot(): void
    {
        $this->assertSame('', Files::extension('noextension'));
    }

    public function testCopyOnMissingSourceReturnsFalse(): void
    {
        $this->assertFalse(Files::copy($this->path('missing.txt'), $this->path('copy/nowhere.txt')));
    }

    public function testReadJsonInvalidReturnsNull(): void
    {
        $path = $this->path('invalid.json');
        Files::write($path, '{invalid json');
        $this->assertNull(Files::readJson($path));
    }

    public function testWriteAndReadCsvWithEmptyData(): void
    {
        $path = $this->path('empty.csv');
        $this->assertTrue(Files::writeCsv($path, []));
        $this->assertSame([], Files::readCsv($path));
    }

    public function testHashWithInvalidAlgorithmThrowsValueError(): void
    {
        $file = $this->createFile('data.bin', 'x');
        $this->expectException(ValueError::class);
        Files::hash($file, 'nope-algo');
    }

    public function testListFilesPatternNoMatch(): void
    {
        $this->createFile('a.txt');
        $this->assertSame([], Files::listFiles($this->testDir, '*.log'));
    }

    public function testReadWriteLargeContent(): void
    {
        $content = str_repeat('A', 256 * 1024); // 256KB
        $path = $this->path('large.txt');
        Files::write($path, $content);

        $this->assertSame(strlen($content), Files::size($path));
        $this->assertSame($content, Files::read($path));
    }
}

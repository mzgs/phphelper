<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpHelper\Str;

final class StrTest extends TestCase
{
    public function testStartsWithSupportsArraysAndCaseSensitivity(): void
    {
        $this->assertTrue(Str::startsWith('Hello', 'He'));
        $this->assertTrue(Str::startsWith('Hello', ['x', 'He']));
        $this->assertTrue(Str::startsWith('Hello', 'he', false));
        $this->assertFalse(Str::startsWith('Hello', 'he'));
        $this->assertFalse(Str::startsWith('Hello', ['x', '']));
    }

    public function testEndsWithSupportsArraysAndCaseSensitivity(): void
    {
        $this->assertTrue(Str::endsWith('filename.TXT', '.TXT'));
        $this->assertTrue(Str::endsWith('filename.TXT', '.txt', false));
        $this->assertFalse(Str::endsWith('filename.TXT', '.txt'));
        $this->assertTrue(Str::endsWith('abc', ['x', 'bc']));
        $this->assertFalse(Str::endsWith('abc', ['x', '']));
    }

    public function testContainsSupportsArraysAndCaseSensitivity(): void
    {
        $this->assertTrue(Str::contains('alphabet', 'ph'));
        $this->assertTrue(Str::contains('alphabet', 'PH', false));
        $this->assertFalse(Str::contains('alphabet', 'PH'));
        $this->assertTrue(Str::contains('alphabet', ['xy', 'ha']));
        $this->assertFalse(Str::contains('alphabet', ['xy', '']));
    }

    public function testBeforeReturnsSubstringBeforeFirstOccurrence(): void
    {
        $this->assertSame('Hello ', Str::before('Hello [World]!', '['));
        $this->assertSame('Hello ', Str::before('Hello [World]!', '[', false));
        $this->assertSame('abc', Str::before('abc', 'x')); // not found returns original
        $this->assertSame('abcABC', Str::before('abcABC', 'z', false));
        $this->assertSame('foo', Str::before('foo/bar/baz', '/'));
        $this->assertSame('foo', Str::before('foo/bar/baz', '/', true));
    }

    public function testAfterReturnsSubstringAfterFirstOccurrence(): void
    {
        $this->assertSame('World]!', Str::after('Hello World]!', ' '));
        $this->assertSame('ello', Str::after('Hello', 'H'));
        $this->assertSame('ello', Str::after('Hello', 'h', false));
        $this->assertSame('abc', Str::after('abc', 'x')); // not found returns original
    }

    public function testBetweenExtractsContentOrReturnsNull(): void
    {
        $this->assertSame('World', Str::between('Hello [World]!', '[', ']'));
        $this->assertSame('bar', Str::between('foo<bar>baz', '<', '>'));
        $this->assertNull(Str::between('no markers', '[', ']'));
        $this->assertNull(Str::between('mismatch ]first[', '[', ']'));
        $this->assertSame('VALUE', Str::between('key:VALUE:end', 'key:', ':end', false));
    }

    public function testReplaceFirstAndLast(): void
    {
        $this->assertSame('baz_bar_foo', Str::replaceFirst('foo_bar_foo', 'foo', 'baz'));
        $this->assertSame('foo_bar_baz', Str::replaceLast('foo_bar_foo', 'foo', 'baz'));

        $this->assertSame('Yello', Str::replaceFirst('Hello', 'h', 'Y', false));
        $this->assertSame('helLo', Str::replaceLast('hello', 'l', 'L'));

        $this->assertSame('abc', Str::replaceFirst('abc', 'x', 'y'));
        $this->assertSame('abc', Str::replaceLast('abc', 'x', 'y'));
    }

    public function testContainsAllHonorsCaseSensitivity(): void
    {
        $this->assertTrue(Str::containsAll('abcde', ['a', 'de']));
        $this->assertFalse(Str::containsAll('abcde', ['a', 'xy']));
        $this->assertTrue(Str::containsAll('ABC', ['a', 'b', 'c'], false));
    }

    public function testEnsurePrefixAndSuffix(): void
    {
        $this->assertSame('/path', Str::ensurePrefix('path', '/'));
        $this->assertSame('/path', Str::ensurePrefix('/path', '/'));
        $this->assertSame('file.txt', Str::ensureSuffix('file', '.txt'));
        $this->assertSame('file.TXT', Str::ensureSuffix('file.TXT', '.txt', false));
    }

    public function testSquishCollapsesWhitespace(): void
    {
        $this->assertSame('Hello world', Str::squish(" \tHello\n  world\r\n "));
        $this->assertSame('', Str::squish('   '));
    }

    public function testSlugProducesUrlFriendlyStrings(): void
    {
        $this->assertSame('hello-world', Str::slug('Hello, World!'));
        $this->assertSame('multiple-spaces', Str::slug('  multiple   spaces  '));
        $this->assertSame('foo-bar-baz', Str::slug('foo_bar--baz'));
    }

    public function testCamelSnakeStudlyConversions(): void
    {
        $this->assertSame('helloWorld', Str::camel('hello_world'));
        $this->assertSame('helloWorldFooBar', Str::camel('Hello world-foo_bar'));

        $this->assertSame('hello_world', Str::snake('helloWorld'));
        $this->assertSame('hello_world_foo_bar', Str::snake('Hello world-FooBar'));
        $this->assertSame('', Str::snake(''));

        $this->assertSame('HelloWorld', Str::studly('hello_world'));
        $this->assertSame('FooBarBaz', Str::studly('foo bar-baz'));
    }

    public function testLowerUpperTitleWithOptionalLocale(): void
    {
        $this->assertSame('hello world', Str::lower('Hello World'));
        $this->assertSame('HELLO WORLD', Str::upper('hello world'));
        $this->assertSame('Hello World', Str::title('hello world'));

        $upperI = 'İ';
        $lowerDotlessI = 'ı';

        $this->assertSame('istanbul', Str::lower($upperI . 'STANBUL', 'tr'));
        $this->assertSame($upperI . 'STANBUL', Str::upper('istanbul', 'tr'));
        $this->assertSame($upperI . 'stanbul', Str::title('istanbul', 'tr'));
        $this->assertSame('Irmak', Str::title(strtoupper($lowerDotlessI . 'rmak'), 'tr'));

        $turkishSentenceLower = 'başlık türkçe isim çöğş';
        $turkishSentenceUpper = 'BAŞLIK TÜRKÇE İSİM ÇÖĞŞ';

        $this->assertSame($turkishSentenceUpper, Str::upper('Başlık türkçe isim çöğş', 'tr'));
        $this->assertSame($turkishSentenceLower, Str::lower($turkishSentenceUpper, 'tr'));
        $this->assertSame('Başlık Türkçe İsim Çöğş', Str::title($turkishSentenceUpper, 'tr'));
    }

    public function testLimitCharacters(): void
    {
        $this->assertSame('abc...', Str::limit('abcdef', 3));
        $this->assertSame('abcdef', Str::limit('abcdef', 10));
        $this->assertSame('...',(Str::limit('abcdef', 0)));
        $this->assertSame('abcd', Str::limit('abcd', 4));
    }

    public function testWordsLimitsWordCount(): void
    {
        $this->assertSame('one two...', Str::words('one two three four', 2));
        $this->assertSame('one   two', Str::words(' one   two  ', 5));
        $this->assertSame('', Str::words('   '));
    }

    public function testRandomStringGeneratesAlphanumericOfRequestedLength(): void
    {
        $s16 = Str::randomString(16);
        $this->assertSame(16, strlen($s16));
        $this->assertSame(0, preg_match('/[^A-Za-z0-9]/', $s16));

        $s1 = Str::randomString(1);
        $this->assertSame(1, strlen($s1));

        $this->assertSame('', Str::randomString(0));
        $this->assertSame('', Str::randomString(-5));
    }

    public function testUuid4FormatAndVariant(): void
    {
        $uuid = Str::uuid4();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    public function testIsJsonValidatesJsonStrings(): void
    {
        $this->assertTrue(Str::isJson('{"a":1}'));
        $this->assertTrue(Str::isJson('[1,2,3]'));
        $this->assertFalse(Str::isJson(''));
        $this->assertFalse(Str::isJson('{invalid json}'));
    }

    public function testNormalizeEolConvertsToNewlines(): void
    {
        $this->assertSame("a\nb\nc\n", Str::normalizeEol("a\r\nb\rc\n"));
    }

    public function testIsEmptyHandlesNullAndWhitespace(): void
    {
        $this->assertTrue(Str::isEmpty(null));
        $this->assertTrue(Str::isEmpty(''));
        $this->assertTrue(Str::isEmpty('   '));
        $this->assertFalse(Str::isEmpty('   ', false));
        $this->assertFalse(Str::isEmpty('x'));
    }

    public function testEmptyArraysAndEmptySearchEdgeCases(): void
    {
        // contains([]) should be false; containsAll([]) should be true
        $this->assertFalse(Str::contains('abc', []));
        $this->assertTrue(Str::containsAll('abc', []));

        // Arrays containing only empty strings
        $this->assertFalse(Str::contains('abc', ['']));
        $this->assertTrue(Str::containsAll('abc', ['']));

        // startsWith/endsWith ignore empty parts and return false if only empty provided
        $this->assertFalse(Str::startsWith('abc', ['']));
        $this->assertFalse(Str::endsWith('abc', ['']));

        // Empty search behavior for before/after/replaceFirst/replaceLast
        $this->assertSame('abc', Str::before('abc', ''));
        $this->assertSame('abc', Str::after('abc', ''));
        $this->assertSame('abc', Str::replaceFirst('abc', '', 'X'));
        $this->assertSame('abc', Str::replaceLast('abc', '', 'X'));

        // between requires non-empty delimiters
        $this->assertNull(Str::between('a[b]c', '', ']'));
        $this->assertNull(Str::between('a[b]c', '[', ''));

        // ensurePrefix/ensureSuffix with empty boundaries
        $this->assertSame('path', Str::ensurePrefix('path', ''));
        $this->assertSame('file', Str::ensureSuffix('file', ''));
    }

    public function testExtremelyLongStrings(): void
    {
        $a = str_repeat('a', 20000);
        $b = str_repeat('b', 20000);
        $s = $a . 'XYZ' . $b;

        // before/after with long input
        $this->assertSame($a, Str::before($s, 'XYZ'));
        $this->assertSame($b, Str::after($s, 'XYZ'));

        // startsWith/endsWith and containsAll on long strings
        $this->assertTrue(Str::startsWith($s, [$a]));
        $this->assertTrue(Str::endsWith($s, [$b]));
        $this->assertTrue(Str::containsAll($s, ['a', 'XYZ', 'b']));

        // limit with negative and small limits
        $this->assertSame('...', Str::limit($s, -5));
        $this->assertStringEndsWith('...', Str::limit($s, 10));

        // words with zero should produce only the end suffix
        $words = implode(' ', array_fill(0, 50, 'w'));
        $this->assertSame('...', Str::words($words, 0));
    }

    public function testSlugEdgeCases(): void
    {
        $this->assertSame('', Str::slug('---'));
        // Allow environment-dependent transliteration results: 'cafe' or 'caf-e'
        $this->assertMatchesRegularExpression('/^caf-?e$/', Str::slug('café'));
    }

    public function testSeoFileNameNormalizesAndKeepsExtensions(): void
    {
        $result = Str::seoFileName('Café résumé.txt');

        $this->assertSame(strtolower($result), $result, 'Result must be lowercase');
        $this->assertStringEndsWith('.txt', $result);
        $this->assertMatchesRegularExpression('/^[a-z0-9.-]+$/', $result);
        $this->assertMatchesRegularExpression('/^c/', $result);
    }

    public function testSeoFileNameTrimsPunctuationAndSpaces(): void
    {
        $this->assertSame('my-file', Str::seoFileName('  ..My file..  '));
    }

    public function testSeoFileNameFallsBackWhenTransliterationFails(): void
    {
        $this->assertSame('', Str::seoFileName("\xff"));
    }

    public function testSeoFileNameHandlesTurkishCharacters(): void
    {
        $this->assertSame('istanbul-calisma.pdf', Str::seoFileName('İstanbul Çalışma.PDF'));
    }

    public function testSeoFileNameTransformsDottedCapitalI(): void
    {
        $this->assertSame('dosya-icerik.png', Str::seoFileName('Dosya İÇerik.PnG'));
    }

    public function testSeoUrlStripsDotsAndDelegatesToSeoFileName(): void
    {
        $this->assertSame('pictureprofile-v12', Str::seoUrl('Picture.profile v1.2'));
        $this->assertSame('', Str::seoUrl('...---'));
    }

    public function testSeoUrlHandlesTurkishCharactersAndMixedCase(): void
    {
        $this->assertSame('istanbulproje-v1ozel', Str::seoUrl('İstanbul.Proje V1.ÖZEL'));
    }

    public function testSeoUrlTransformsDottedCapitalI(): void
    {
        $this->assertSame('iyi-haberler', Str::seoUrl('İyi Haberler'));
    }

    public function testUuid4GeneratesUniqueValues(): void
    {
        $u1 = Str::uuid4();
        $u2 = Str::uuid4();
        $this->assertNotSame($u1, $u2);
    }

    public function testNormalizeEolNoChangesWhenAlreadyLfOrNoEol(): void
    {
        $this->assertSame("line1\nline2\n", Str::normalizeEol("line1\nline2\n"));
        $this->assertSame('plain', Str::normalizeEol('plain'));
    }
}

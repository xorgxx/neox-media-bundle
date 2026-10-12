<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\Storage;

use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Storage\SubjectDirectoryNamer;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Mapping\PropertyMapping;

final class SubjectDirectoryNamerTest extends TestCase
{
    public function testReturnsSanitizedStorageDir(): void
    {
        $media = (new Media())->setStorageDir('acme-corp');

        self::assertSame('acme-corp', (new SubjectDirectoryNamer())->directoryName($media, $this->mapping()));
    }

    public function testNullStorageDirReturnsRoot(): void
    {
        self::assertSame('', (new SubjectDirectoryNamer())->directoryName(new Media(), $this->mapping()));
    }

    public function testNonMediaSubjectReturnsEmpty(): void
    {
        self::assertSame('', (new SubjectDirectoryNamer())->directoryName(new \stdClass(), $this->mapping()));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('sanitizeProvider')]
    public function testSanitizeBlocksTraversal(string $input, string $expected): void
    {
        self::assertSame($expected, SubjectDirectoryNamer::sanitize($input));
    }

    /** @return iterable<string, array{0: string, 1: string}> */
    public static function sanitizeProvider(): iterable
    {
        yield 'simple slug' => ['acme-corp', 'acme-corp'];
        yield 'path traversal' => ['../../etc/passwd', 'etc-passwd'];
        yield 'backslashes' => ['a\\b\\c', 'a-b-c'];
        yield 'forward slashes' => ['a/b/c', 'a-b-c'];
        yield 'spaces' => ['acme corp', 'acme-corp'];
        yield 'unicode' => ['société-42', 'soci-t-42'];
        yield 'nul byte' => ["evil\0dir", 'evil-dir'];
        yield 'dots only' => ['..', ''];
        yield 'mixed hostile' => ['../a..b/', 'a-b'];
    }

    private function mapping(): PropertyMapping
    {
        // PropertyMapping is final — instantiate it (paths are irrelevant here).
        return new PropertyMapping('file', 'filename');
    }
}

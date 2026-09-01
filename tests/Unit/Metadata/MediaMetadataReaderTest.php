<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\Metadata;

use Neox\MediaBundle\Tests\Fixture\DummyEntity;
use Neox\MediaBundle\Metadata\MediaMetadataReader;
use PHPUnit\Framework\TestCase;

final class MediaMetadataReaderTest extends TestCase
{
    public function testReadsMediaFieldAttributes(): void
    {
        $reader = new MediaMetadataReader();
        $definitions = $reader->read(DummyEntity::class);

        self::assertCount(2, $definitions);
    }

    public function testCoverFieldHasCorrectRole(): void
    {
        $reader = new MediaMetadataReader();
        $definitions = $reader->read(DummyEntity::class);

        $cover = $definitions[0];
        self::assertSame('cover', $cover->property);
        self::assertSame('cover', $cover->role);
        self::assertSame(['image/*'], $cover->accept);
        self::assertFalse($cover->multiple);
        self::assertFalse($cover->sortable);
    }

    public function testGalleryFieldHasMultipleAndSortable(): void
    {
        $reader = new MediaMetadataReader();
        $definitions = $reader->read(DummyEntity::class);

        $gallery = $definitions[1];
        self::assertSame('gallery', $gallery->property);
        self::assertSame('gallery', $gallery->role);
        self::assertSame(['image/*', 'video/*'], $gallery->accept);
        self::assertTrue($gallery->multiple);
        self::assertTrue($gallery->sortable);
    }

    public function testReadsFromObjectInstance(): void
    {
        $reader = new MediaMetadataReader();
        $definitions = $reader->read(new DummyEntity());

        self::assertCount(2, $definitions);
    }

    public function testReturnsEmptyArrayForClassWithoutAttributes(): void
    {
        $reader = new MediaMetadataReader();
        $definitions = $reader->read(\stdClass::class);

        self::assertSame([], $definitions);
    }
}

<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\Bridge\Crud;

use Neox\MediaBundle\Bridge\Crud\MediaCrudFieldGuesser;
use Neox\MediaBundle\Form\MediaPickerType;
use Neox\MediaBundle\Metadata\MediaMetadataReader;
use Neox\MediaBundle\Tests\Fixture\DummyEntity;
use PHPUnit\Framework\TestCase;

final class MediaCrudFieldGuesserTest extends TestCase
{
    public function testGuessReturnsMediaPickerTypeForEachField(): void
    {
        $guesser = new MediaCrudFieldGuesser(new MediaMetadataReader());
        $fields = $guesser->guess(DummyEntity::class);

        self::assertCount(2, $fields);
        self::assertArrayHasKey('cover', $fields);
        self::assertArrayHasKey('gallery', $fields);
    }

    public function testCoverFieldHasCorrectOptions(): void
    {
        $guesser = new MediaCrudFieldGuesser(new MediaMetadataReader());
        $fields = $guesser->guess(DummyEntity::class);

        self::assertSame(MediaPickerType::class, $fields['cover']['type']);
        self::assertFalse($fields['cover']['options']['multiple']);
        self::assertFalse($fields['cover']['options']['sortable']);
        self::assertSame(['image/*'], $fields['cover']['options']['accept']);
    }

    public function testGalleryFieldHasMultipleAndSortable(): void
    {
        $guesser = new MediaCrudFieldGuesser(new MediaMetadataReader());
        $fields = $guesser->guess(DummyEntity::class);

        self::assertTrue($fields['gallery']['options']['multiple']);
        self::assertTrue($fields['gallery']['options']['sortable']);
        self::assertSame(['image/*', 'video/*'], $fields['gallery']['options']['accept']);
    }

    public function testGuessReturnsEmptyArrayForClassWithoutAttributes(): void
    {
        $guesser = new MediaCrudFieldGuesser(new MediaMetadataReader());
        $fields = $guesser->guess(\stdClass::class);

        self::assertSame([], $fields);
    }
}

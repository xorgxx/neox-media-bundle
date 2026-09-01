<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\Service;

use Liip\ImagineBundle\Service\FilterService;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;
use Neox\MediaBundle\Service\MediaUrlGenerator;
use Neox\MediaBundle\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Templating\Helper\UploaderHelperInterface;

final class MediaUrlGeneratorTest extends TestCase
{
    private function createMedia(MediaSource $source, ?string $url = null, ?string $embedUrl = null, ?string $storageKey = null): Media
    {
        $media = new Media();
        $media->setSource($source);
        $media->setType(MediaType::IMAGE);
        if ($url !== null) $media->setUrl($url);
        if ($embedUrl !== null) $media->setEmbedUrl($embedUrl);
        if ($storageKey !== null) $media->setStorageKey($storageKey);

        return $media;
    }

    public function testFileSourceReturnsUploaderAsset(): void
    {
        $uploader = $this->createMock(UploaderHelperInterface::class);
        $uploader->method('asset')->willReturn('/uploads/media/photo.jpg');

        $generator = new MediaUrlGenerator($uploader, $this->createMock(FilterService::class));
        $media = $this->createMedia(MediaSource::FILE);

        self::assertSame('/uploads/media/photo.jpg', $generator->url($media));
    }

    public function testUrlSourceReturnsMediaUrl(): void
    {
        $uploader = $this->createMock(UploaderHelperInterface::class);
        $generator = new MediaUrlGenerator($uploader, $this->createMock(FilterService::class));
        $media = $this->createMedia(MediaSource::URL, 'https://example.com/image.jpg');

        self::assertSame('https://example.com/image.jpg', $generator->url($media));
    }

    public function testEmbedSourceReturnsEmbedUrl(): void
    {
        $uploader = $this->createMock(UploaderHelperInterface::class);
        $generator = new MediaUrlGenerator($uploader, $this->createMock(FilterService::class));
        $media = $this->createMedia(MediaSource::EMBED, embedUrl: 'https://www.youtube.com/embed/abc123');

        self::assertSame('https://www.youtube.com/embed/abc123', $generator->url($media));
    }

    public function testRemoteSourceUsesStorageAdapter(): void
    {
        $uploader = $this->createMock(UploaderHelperInterface::class);
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('resolveUrl')
            ->with('uploads/products/cover.jpg')
            ->willReturn('https://cdn.example.com/media/uploads/products/cover.jpg');

        $generator = new MediaUrlGenerator($uploader, $this->createMock(FilterService::class), $storage);
        $media = $this->createMedia(MediaSource::REMOTE, storageKey: 'uploads/products/cover.jpg');

        self::assertSame('https://cdn.example.com/media/uploads/products/cover.jpg', $generator->url($media));
    }

    public function testRemoteSourceFallsBackToMediaUrlWhenStorageReturnsNull(): void
    {
        $uploader = $this->createMock(UploaderHelperInterface::class);
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('resolveUrl')->willReturn(null);

        $generator = new MediaUrlGenerator($uploader, $this->createMock(FilterService::class), $storage);
        $media = $this->createMedia(MediaSource::REMOTE, url: 'https://fallback.example.com/file.jpg', storageKey: 'some/key');

        self::assertSame('https://fallback.example.com/file.jpg', $generator->url($media));
    }

    public function testRemoteSourceFallsBackToMediaUrlWhenNoStorageAdapter(): void
    {
        $uploader = $this->createMock(UploaderHelperInterface::class);
        $generator = new MediaUrlGenerator($uploader, $this->createMock(FilterService::class), null);
        $media = $this->createMedia(MediaSource::REMOTE, url: 'https://fallback.example.com/file.jpg', storageKey: 'some/key');

        self::assertSame('https://fallback.example.com/file.jpg', $generator->url($media));
    }

    public function testFilterReturnsFilterServiceUrlForFileSource(): void
    {
        $uploader = $this->createMock(UploaderHelperInterface::class);
        $uploader->method('asset')->willReturn('/uploads/media/photo.jpg');

        $filterService = $this->createMock(FilterService::class);
        $filterService->expects(self::once())
            ->method('getUrlOfFilteredImage')
            ->with('uploads/media/photo.jpg', 'neox_media_thumb')
            ->willReturn('/media/cache/neox_media_thumb/uploads/media/photo.jpg');

        $generator = new MediaUrlGenerator($uploader, $filterService);
        $media = $this->createMedia(MediaSource::FILE);

        self::assertSame('/media/cache/neox_media_thumb/uploads/media/photo.jpg', $generator->filter($media, 'neox_media_thumb'));
    }

    public function testFilterReturnsRawUrlForNonFileSource(): void
    {
        $uploader = $this->createMock(UploaderHelperInterface::class);
        $filterService = $this->createMock(FilterService::class);
        $filterService->expects(self::never())->method('getUrlOfFilteredImage');

        $generator = new MediaUrlGenerator($uploader, $filterService);
        $media = $this->createMedia(MediaSource::URL, 'https://example.com/image.jpg');

        self::assertSame('https://example.com/image.jpg', $generator->filter($media, 'neox_media_thumb'));
    }
}

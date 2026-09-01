<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Service;

use Liip\ImagineBundle\Service\FilterService;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;
use Neox\MediaBundle\Storage\StorageInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelperInterface;

final readonly class MediaUrlGenerator
{
    public function __construct(
        private UploaderHelperInterface $uploaderHelper,
        private FilterService $filterService,
        private ?StorageInterface $storage = null,
    ) {
    }

    public function url(Media $media): ?string
    {
        return match ($media->getSource()) {
            MediaSource::FILE => $this->uploaderHelper->asset($media, 'file'),
            MediaSource::URL => $media->getUrl(),
            MediaSource::EMBED => $media->getEmbedUrl(),
            MediaSource::REMOTE => $this->resolveRemote($media),
        };
    }

    /**
     * Generate a LiipImagine filtered URL for a FILE-source media.
     * For non-FILE sources, returns the raw URL (no filtering).
     *
     * @param string $filter A LiipImagine filter set name (e.g. "neox_media_thumb")
     */
    public function filter(Media $media, string $filter): ?string
    {
        if ($media->getSource() === MediaSource::EMBED) {
            $metadata = $media->getMetadata();

            return $metadata['thumbnail'] ?? null;
        }

        if ($media->getSource() !== MediaSource::FILE) {
            return $this->url($media);
        }

        if ($media->getType() !== MediaType::IMAGE) {
            return $this->url($media);
        }

        $path = $this->uploaderHelper->asset($media, 'file');
        if ($path === null) {
            return null;
        }

        try {
            return $this->filterService->getUrlOfFilteredImage(ltrim($path, '/'), $filter);
        } catch (\Throwable) {
            return $this->url($media);
        }
    }

    private function resolveRemote(Media $media): ?string
    {
        if ($this->storage !== null && $media->getStorageKey() !== null) {
            $resolved = $this->storage->resolveUrl($media->getStorageKey());
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return $media->getUrl();
    }
}

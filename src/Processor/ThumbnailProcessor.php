<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Processor;

use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;
use Neox\MediaBundle\Service\MediaUrlGenerator;

/**
 * Generates a thumbnail preview URL for images via LiipImagineBundle.
 * Stores the thumbnail URL in metadata. Does not create a Doctrine row per variant.
 */
final class ThumbnailProcessor implements ProcessorInterface
{
    private const FILTER = 'neox_media_thumb';

    public function __construct(
        private readonly MediaUrlGenerator $urlGenerator,
    ) {
    }

    public function supports(Media $media): bool
    {
        return $media->getType() === MediaType::IMAGE
            && $media->getSource() === MediaSource::FILE
            && $media->getFilename() !== null;
    }

    public function process(Media $media): void
    {
        $url = $this->urlGenerator->filter($media, self::FILTER);
        if ($url === null) {
            return;
        }

        $metadata = $media->getMetadata();
        $metadata['thumbnail_url'] = $url;
        $media->setMetadata($metadata);
    }
}

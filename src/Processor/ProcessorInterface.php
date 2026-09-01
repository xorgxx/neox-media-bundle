<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Processor;

use Neox\MediaBundle\Entity\Media;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('neox_media.processor')]
interface ProcessorInterface
{
    /**
     * Whether this processor can handle the given media.
     */
    public function supports(Media $media): bool;

    /**
     * Process the media. May modify the entity (dimensions, duration, metadata, etc.).
     * Throw an exception to signal failure — the handler will mark the media as FAILED.
     */
    public function process(Media $media): void;
}

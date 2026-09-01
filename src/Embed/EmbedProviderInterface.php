<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Embed;

use Neox\MediaBundle\Entity\Media;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Contract for embed providers (YouTube, Vimeo, Dailymotion, SoundCloud, etc.).
 *
 * A provider is responsible for:
 * 1. Detecting whether a given URL belongs to its platform (supports()).
 * 2. Resolving the URL into a fully-configured Media entity with embedUrl, provider name, type, and metadata.
 *
 * Register your implementation with the tag "neox_media.embed_provider"
 * (auto-applied via #[AutoconfigureTag] on this interface).
 */
#[AutoconfigureTag('neox_media.embed_provider')]
interface EmbedProviderInterface
{
    /**
     * Whether this provider can handle the given URL.
     */
    public function supports(string $url): bool;

    /**
     * Resolve the URL into a Media entity configured for EMBED source.
     * Returns null if the URL is valid but cannot be resolved (e.g. private video).
     */
    public function resolve(string $url): ?Media;

    /**
     * The provider name stored in Media::provider (e.g. "youtube", "vimeo").
     */
    public function getName(): string;
}

<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Embed;

use Neox\MediaBundle\Entity\Media;

/**
 * Chains multiple embed providers and delegates to the first one that supports the URL.
 *
 * Providers are injected via tagged iterator (auto-tagged via #[AutoconfigureTag]
 * on EmbedProviderInterface).
 */
final class EmbedProviderRegistry
{
    /**
     * @param iterable<EmbedProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    /**
     * Find the first provider that supports the given URL and resolve it.
     * Returns null if no provider supports the URL or if resolution fails.
     */
    public function resolve(string $url): ?Media
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($url)) {
                return $provider->resolve($url);
            }
        }

        return null;
    }

    /**
     * Check whether any registered provider supports the given URL.
     */
    public function supports(string $url): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the provider name for a given URL, or null if none supports it.
     */
    public function getProviderName(string $url): ?string
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($url)) {
                return $provider->getName();
            }
        }

        return null;
    }
}

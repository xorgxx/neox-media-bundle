<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Service;

final class UrlValidator
{
    /**
     * @param array<int, string> $allowedSchemes
     * @param array<int, string> $allowedEmbedProviders
     */
    public function __construct(
        private readonly array $allowedSchemes,
        private readonly array $allowedEmbedProviders,
    ) {
    }

    public function validateUrl(string $url): ?string
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            return 'Invalid URL format.';
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, $this->allowedSchemes, true)) {
            return sprintf('URL scheme "%s" is not allowed. Allowed schemes: %s.', $scheme ?: 'none', implode(', ', $this->allowedSchemes));
        }

        if (!isset($parsed['host']) || $parsed['host'] === '') {
            return 'URL must have a valid host.';
        }

        return null;
    }

    public function validateEmbedUrl(string $embedUrl, ?string $provider = null): ?string
    {
        $parsed = parse_url($embedUrl);
        if ($parsed === false) {
            return 'Invalid embed URL format.';
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, $this->allowedSchemes, true)) {
            return sprintf('Embed URL scheme "%s" is not allowed.', $scheme ?: 'none');
        }

        $host = strtolower($parsed['host'] ?? '');
        if ($host === '') {
            return 'Embed URL must have a valid host.';
        }

        if (!in_array($host, $this->allowedEmbedProviders, true)) {
            return sprintf('Embed host "%s" is not in the allowed providers list.', $host);
        }

        return null;
    }

    /** @return array<int, string> */
    public function getAllowedEmbedProviders(): array
    {
        return $this->allowedEmbedProviders;
    }
}

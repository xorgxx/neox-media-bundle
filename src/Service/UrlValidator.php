<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Service;

final class UrlValidator
{
    private const LOCAL_HOSTS = ['localhost', 'localhost.localdomain'];

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

        $host = strtolower($parsed['host'] ?? '');
        if ($host === '') {
            return 'URL must have a valid host.';
        }

        return $this->validatePublicHost($host, 'URL');
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

        $error = $this->validatePublicHost($host, 'Embed URL');
        if ($error !== null) {
            return $error;
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

    private function validatePublicHost(string $host, string $label): ?string
    {
        $normalizedHost = trim($host, '[]');

        if (in_array($normalizedHost, self::LOCAL_HOSTS, true) || str_ends_with($normalizedHost, '.localhost')) {
            return sprintf('%s host "%s" is not allowed.', $label, $host);
        }

        $ip = filter_var($normalizedHost, FILTER_VALIDATE_IP);
        if ($ip === false) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return sprintf('%s host "%s" must be a public IP address.', $label, $host);
        }

        return null;
    }
}

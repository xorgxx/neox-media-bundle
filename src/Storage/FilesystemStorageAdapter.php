<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Storage;

/**
 * Default filesystem adapter with optional CDN prefix.
 *
 * Use this when your files are on a local disk (or NFS) but served
 * through a CDN or static file server.
 *
 * Example:
 *   cdn_prefix = "https://cdn.example.com/media"
 *   storageKey = "uploads/products/cover.jpg"
 *   → resolveUrl returns "https://cdn.example.com/media/uploads/products/cover.jpg"
 */
final class FilesystemStorageAdapter implements StorageInterface
{
    public function __construct(
        private readonly string $cdnPrefix = '',
        private readonly string $projectDir = '',
    ) {
    }

    public function resolveUrl(string $storageKey): string
    {
        if ($this->cdnPrefix === '') {
            return '/uploads/media/'.ltrim($storageKey, '/');
        }

        return rtrim($this->cdnPrefix, '/').'/'.ltrim($storageKey, '/');
    }

    public function exists(string $storageKey): bool
    {
        if ($this->projectDir === '') {
            return false;
        }

        $path = rtrim($this->projectDir, '/').'/public/uploads/media/'.$storageKey;

        return file_exists($path);
    }
}

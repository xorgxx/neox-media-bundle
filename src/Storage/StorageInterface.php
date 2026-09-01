<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Storage;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Contract for remote storage adapters (S3, R2, MinIO, CDN, etc.).
 *
 * The bundle does NOT ship any cloud-specific implementation.
 * The host application implements this or installs a separate adapter package.
 *
 * Register your implementation with the tag "neox_media.storage_adapter"
 * (auto-applied via #[AutoconfigureTag] on this interface).
 */
#[AutoconfigureTag('neox_media.storage_adapter')]
interface StorageInterface
{
    /**
     * Resolve a public URL from a storage key.
     *
     * @param string $storageKey The key stored in Media::storageKey
     *
     * @return string|null The public URL, or null if it cannot be resolved
     */
    public function resolveUrl(string $storageKey): ?string;

    /**
     * Check whether a file exists at the given storage key.
     */
    public function exists(string $storageKey): bool;
}

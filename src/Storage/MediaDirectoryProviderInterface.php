<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Storage;

/**
 * Decides in which physical subdirectory an uploaded media is stored.
 *
 * The bundle never knows the host application's domain: the key is an opaque
 * string decided by the app (company slug, team id, user id, "shared"...).
 *
 * The key is persisted on Media::storageDir at upload time, so URL generation
 * stays stable no matter who looks at the media afterwards.
 *
 * Return null to store the file at the root of the upload destination
 * (historical flat behaviour).
 *
 * Example implementations live in examples/:
 *  - CompanyDirectoryProvider   → uploads/media/<company-slug>/
 *  - UserDirectoryProvider      → uploads/media/user-<id>/
 */
interface MediaDirectoryProviderInterface
{
    /**
     * Opaque directory key for the file being uploaded, or null for root.
     * The bundle sanitizes it (no "..", "/", "\") before use.
     */
    public function getDirectoryKey(): ?string;
}

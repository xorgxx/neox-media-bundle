<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Storage;

use Neox\MediaBundle\Entity\Media;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * VichUploader directory namer reading Media::storageDir.
 *
 * The directory comes from persisted entity state (set once at upload time
 * by a MediaDirectoryProviderInterface), never from the current security
 * context — so a file uploaded under "acme-corp" keeps resolving to
 * uploads/media/acme-corp/ whoever generates its URL.
 *
 * Register on the neox_media mapping in the host app:
 *
 *     vich_uploader:
 *         mappings:
 *             neox_media:
 *                 directory_namer:
 *                     service: Neox\MediaBundle\Storage\SubjectDirectoryNamer
 */
/**
 * @implements DirectoryNamerInterface<object>
 */
final class SubjectDirectoryNamer implements DirectoryNamerInterface
{
    /** @param object|array<array-key, mixed> $object */
    public function directoryName(object|array $object, PropertyMapping $mapping): string
    {
        if ($object instanceof Media) {
            return self::sanitize((string) ($object->getStorageDir() ?? ''));
        }

        return '';
    }

    /**
     * Reduce an arbitrary key to a safe single path segment.
     *
     * Blocks path traversal (".."), separators and control chars, and keeps
     * only [a-zA-Z0-9-_], everything else becomes '-'.
     */
    public static function sanitize(string $key): string
    {
        $key = trim($key);
        $key = str_replace(["\0", '/', '\\', '..'], '-', $key);
        $key = (string) preg_replace('/[^a-zA-Z0-9_-]/', '-', $key);
        $key = (string) preg_replace('/-{2,}/', '-', $key);
        $key = trim($key, '-.');

        // Never produce a hidden dir or an empty segment from hostile input.
        return $key;
    }
}

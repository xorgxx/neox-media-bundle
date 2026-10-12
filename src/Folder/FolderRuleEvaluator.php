<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Folder;

use Doctrine\ORM\QueryBuilder;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaType;

/**
 * Translates a MediaCollection::rules array into DQL criteria on a Media
 * query builder — the content of a dynamic folder.
 *
 * Rules are plain arrays, e.g.:
 *   [
 *       'type' => 'image',
 *       'mime_prefix' => 'image/',
 *       'owner' => 'team:marketing',
 *       'storage_dir' => 'acme-corp',
 *       'filename_prefix' => 'repertoire/produits/',
 *       'title_contains' => 'logo',
 *       'source' => 'file',
 *       'created_after' => '2026-01-01',
 *       'size_min' => 1024,
 *       'metadata' => ['role' => 'cover'],
 *   ]
 *
 * SECURITY: only keys listed in the allowed_fields config are evaluated —
 * arbitrary rule keys are ignored, never injected into DQL.
 */
final class FolderRuleEvaluator
{
    /**
     * @param array<int, string> $allowedFields Whitelist from neox_media.folders.rules.allowed_fields
     */
    public function __construct(
        private readonly array $allowedFields,
    ) {
    }

    /**
     * Apply the dynamic rules of a folder onto a "m"-aliased Media QueryBuilder.
     *
     * @param array<string, mixed> $rules
     */
    public function apply(QueryBuilder $qb, array $rules): void
    {
        $i = 0;
        $param = static function (mixed $value) use (&$i, $qb): string {
            $name = 'fr_'.$i++;
            $qb->setParameter($name, $value);

            return ':'.$name;
        };

        $allowed = fn (string $field): bool => in_array($field, $this->allowedFields, true);

        if (isset($rules['type']) && $allowed('type')) {
            $type = MediaType::tryFrom((string) $rules['type']);
            if ($type !== null) {
                $qb->andWhere('m.type = '.$param($type));
            }
        }

        if (isset($rules['source']) && $allowed('source')) {
            $source = MediaSource::tryFrom((string) $rules['source']);
            if ($source !== null) {
                $qb->andWhere('m.source = '.$param($source));
            }
        }

        if (isset($rules['mime_prefix']) && $allowed('mime_prefix')) {
            $qb->andWhere('m.mimeType LIKE '.$param(((string) $rules['mime_prefix']).'%'));
        }

        if (isset($rules['owner']) && $allowed('owner')) {
            $qb->andWhere('m.owner = '.$param((string) $rules['owner']));
        }

        if (isset($rules['storage_dir']) && $allowed('storage_dir')) {
            $qb->andWhere('m.storageDir = '.$param((string) $rules['storage_dir']));
        }

        if (isset($rules['filename_prefix']) && $allowed('filename_prefix')) {
            $qb->andWhere('m.filename LIKE '.$param(((string) $rules['filename_prefix']).'%'));
        }

        if (isset($rules['title_contains']) && $allowed('title_contains')) {
            $qb->andWhere('LOWER(m.title) LIKE '.$param('%'.mb_strtolower((string) $rules['title_contains']).'%'));
        }

        if (isset($rules['created_after']) && $allowed('created_after')) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $rules['created_after']);
            if ($date !== false) {
                $qb->andWhere('m.createdAt >= '.$param($date->setTime(0, 0)));
            }
        }

        if (isset($rules['created_before']) && $allowed('created_before')) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $rules['created_before']);
            if ($date !== false) {
                $qb->andWhere('m.createdAt <= '.$param($date->setTime(23, 59, 59)));
            }
        }

        if (isset($rules['size_min']) && $allowed('size_min')) {
            $qb->andWhere('m.size >= '.$param((int) $rules['size_min']));
        }

        if (isset($rules['size_max']) && $allowed('size_max')) {
            $qb->andWhere('m.size <= '.$param((int) $rules['size_max']));
        }

        // Best-effort JSON metadata match: portable LIKE on the serialized
        // column ("\"key\":\"value\""), no platform-specific JSON functions.
        if (isset($rules['metadata']) && is_array($rules['metadata']) && $allowed('metadata')) {
            foreach ($rules['metadata'] as $key => $value) {
                if (!is_string($key) || (!is_scalar($value) && $value !== null)) {
                    continue;
                }
                $needle = sprintf('"%s":"%s"', $key, (string) $value);
                $qb->andWhere('m.metadata LIKE '.$param('%'.addcslashes($needle, '%_').'%'));
            }
        }
    }
}

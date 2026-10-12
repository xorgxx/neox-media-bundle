<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Ownership;

/**
 * Default provider used when neox_media.ownership.enabled is false.
 *
 * Everything stays global: no owner is assigned and no subject keys exist,
 * so repository scoping and voters are effectively bypassed.
 */
final class NullMediaOwnerProvider implements MediaOwnerProviderInterface
{
    public function getCurrentOwnerKey(): ?string
    {
        return null;
    }

    public function getCurrentSubjectKeys(): array
    {
        return [];
    }
}

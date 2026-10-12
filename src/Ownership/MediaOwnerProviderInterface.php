<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Ownership;

/**
 * Resolves the current "owner" and "subject" keys for the active user.
 *
 * The bundle NEVER knows the host application's User entity. All identifiers
 * are opaque strings produced by this provider.
 *
 * Typical keys:
 *  - owner key:   "user:42", "company:acme-corp"
 *  - subject keys: ["user:42", "role:ROLE_EDITOR", "team:marketing"]
 *
 * Return null / empty array when there is no authenticated user or when the
 * application runs in "global" mode (no ownership).
 */
interface MediaOwnerProviderInterface
{
    /**
     * Opaque key identifying who owns newly created media.
     * Stored in Media::owner / MediaCollection::owner at creation time.
     */
    public function getCurrentOwnerKey(): ?string;

    /**
     * All keys the current user can act as, used to resolve MediaShare ACLs.
     * Should include the owner key, role keys ("role:ROLE_X") and any
     * app-level keys ("team:x", "company:y").
     *
     * @return array<int, string>
     */
    public function getCurrentSubjectKeys(): array;
}

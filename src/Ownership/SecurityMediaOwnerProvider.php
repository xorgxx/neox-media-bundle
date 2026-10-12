<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Ownership;

use Symfony\Bundle\SecurityBundle\Security;

/**
 * Default ownership provider based on Symfony Security.
 *
 * Produces keys without knowing the concrete User class:
 *  - owner:   "user:<getUserIdentifier()>"
 *  - subjects: owner key + "role:<ROLE>" for each role
 *
 * The host application can register its own MediaOwnerProviderInterface
 * service to add app-level keys ("team:x", "company:y", "tenant:z").
 */
final class SecurityMediaOwnerProvider implements MediaOwnerProviderInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function getCurrentOwnerKey(): ?string
    {
        $user = $this->security->getUser();

        if ($user === null) {
            return null;
        }

        return 'user:'.$user->getUserIdentifier();
    }

    public function getCurrentSubjectKeys(): array
    {
        $user = $this->security->getUser();

        if ($user === null) {
            return [];
        }

        $keys = ['user:'.$user->getUserIdentifier()];

        foreach ($user->getRoles() as $role) {
            $keys[] = 'role:'.$role;
        }

        return $keys;
    }
}

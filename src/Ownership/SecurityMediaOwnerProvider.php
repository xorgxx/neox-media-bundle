<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Ownership;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Default ownership provider based on Symfony Security.
 *
 * Produces keys without knowing the concrete User class:
 *  - owner:   "user:<getUserIdentifier()>"
 *  - subjects: owner key + "role:<ROLE>" for each effective role
 *
 * Roles are read from the token via getRoleNames() — the only API common to
 * Symfony 7 and Symfony 8 (UserInterface::getRoles() was removed in SF8),
 * and it already includes role-hierarchy expansion.
 *
 * The host application can register its own MediaOwnerProviderInterface
 * service to add app-level keys ("team:x", "company:y", "tenant:z").
 */
final class SecurityMediaOwnerProvider implements MediaOwnerProviderInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function getCurrentOwnerKey(): ?string
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        return $user instanceof UserInterface
            ? 'user:'.$user->getUserIdentifier()
            : null;
    }

    public function getCurrentSubjectKeys(): array
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if (!$user instanceof UserInterface) {
            return [];
        }

        $keys = ['user:'.$user->getUserIdentifier()];

        foreach ($token->getRoleNames() as $role) {
            $keys[] = 'role:'.$role;
        }

        return $keys;
    }
}

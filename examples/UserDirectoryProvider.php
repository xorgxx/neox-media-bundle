<?php

declare(strict_types=1);

namespace App\Media;

use Neox\MediaBundle\Storage\MediaDirectoryProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Example 2 — one physical directory per uploader (user).
 *
 * Files land in public/uploads/media/user-42/.
 *
 * Note: this only organizes files on disk — it grants NO isolation.
 * For visibility rules, enable neox_media.ownership instead.
 */
final class UserDirectoryProvider implements MediaDirectoryProviderInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function getDirectoryKey(): ?string
    {
        $user = $this->security->getUser();

        return $user !== null ? 'user-'.$user->getUserIdentifier() : null;
    }
}

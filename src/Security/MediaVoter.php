<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Security;

use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Entity\MediaCollection;
use Neox\MediaBundle\Entity\MediaShare;
use Neox\MediaBundle\Enum\MediaVisibility;
use Neox\MediaBundle\Ownership\MediaOwnerProviderInterface;
use Neox\MediaBundle\Repository\MediaShareRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides access on Media and MediaCollection when ownership is enabled.
 *
 * Attributes:
 *  - MEDIA_VIEW    view url, thumbnails, metadata
 *  - MEDIA_EDIT    rename, edit metadata, re-upload
 *  - MEDIA_DELETE  delete the media
 *  - MEDIA_SHARE   create/remove MediaShare entries
 *
 * Rules (in order):
 *  1. ownership disabled → grant everything (legacy global mode)
 *  2. admin bypass role  → grant everything
 *  3. owner              → grant everything
 *  4. visibility != PRIVATE on the target → VIEW only
 *  5. matching MediaShare → VIEW, or EDIT/SHARE if canEdit/canReshare
 *
 * The host app can replace this voter (its own voter runs first when
 * registered with a higher priority or when it votes on the same subject).
 *
 * @extends Voter<string, Media|MediaCollection>
 */
final class MediaVoter extends Voter
{
    public const VIEW = 'MEDIA_VIEW';
    public const EDIT = 'MEDIA_EDIT';
    public const DELETE = 'MEDIA_DELETE';
    public const SHARE = 'MEDIA_SHARE';

    public function __construct(
        private readonly bool $ownershipEnabled,
        private readonly string $adminBypassRole,
        private readonly MediaOwnerProviderInterface $ownerProvider,
        private readonly MediaShareRepository $shareRepository,
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::SHARE], true)) {
            return false;
        }

        return $subject instanceof Media || $subject instanceof MediaCollection;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        // Legacy global mode: everything allowed to whoever reaches the voter.
        if (!$this->ownershipEnabled) {
            return true;
        }

        // Admin bypass (configurable role). getUser() first: isGranted()
        // throws when no token exists (fully anonymous request).
        if ($this->adminBypassRole !== ''
            && $this->security->getUser() !== null
            && $this->security->isGranted($this->adminBypassRole)
        ) {
            return true;
        }

        // supports() guarantees $subject is Media|MediaCollection.
        [$ownerKey, $visibility, $targetType, $targetId] = $subject instanceof Media
            ? [
                $subject->getOwner(),
                $subject->getVisibility(),
                MediaShare::TARGET_MEDIA,
                $subject->getId(),
            ]
            : [
                $subject->getOwner(),
                $subject->getVisibility(),
                MediaShare::TARGET_COLLECTION,
                $subject->getId(),
            ];

        // Owner has full rights.
        $currentOwner = $this->ownerProvider->getCurrentOwnerKey();
        if ($ownerKey !== null && $currentOwner !== null && $ownerKey === $currentOwner) {
            return true;
        }

        // A target with no owner is global → visible/editable by anyone logged in.
        if ($ownerKey === null && $this->security->getUser() !== null) {
            return true;
        }

        // Not persisted → no share/visibility resolution possible.
        if ($targetId === null) {
            return false;
        }

        // Shared/public visibility grants view.
        if ($attribute === self::VIEW && $visibility !== MediaVisibility::PRIVATE) {
            return $this->security->getUser() !== null || $visibility === MediaVisibility::PUBLIC;
        }

        // ACL check.
        $subjectKeys = $this->ownerProvider->getCurrentSubjectKeys();
        if ($subjectKeys === []) {
            return false;
        }

        $shares = $this->shareRepository->findForTarget($targetType, $targetId, $subjectKeys);

        foreach ($shares as $share) {
            if ($attribute === self::VIEW) {
                return true;
            }
            if (($attribute === self::EDIT || $attribute === self::DELETE) && $share->canEdit()) {
                return true;
            }
            if ($attribute === self::SHARE && ($share->canReshare() || $share->canEdit())) {
                return true;
            }
        }

        return false;
    }
}

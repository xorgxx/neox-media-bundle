<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Tests\Unit\Security;

use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Entity\MediaShare;
use Neox\MediaBundle\Enum\MediaVisibility;
use Neox\MediaBundle\Ownership\MediaOwnerProviderInterface;
use Neox\MediaBundle\Repository\MediaShareRepository;
use Neox\MediaBundle\Security\MediaVoter;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class MediaVoterTest extends TestCase
{
    public function testLegacyModeGrantsEverything(): void
    {
        $voter = $this->makeVoter(ownershipEnabled: false);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->createMock(TokenInterface::class), new Media(), [MediaVoter::VIEW])
        );
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->createMock(TokenInterface::class), new Media(), [MediaVoter::DELETE])
        );
    }

    public function testAbstainsOnUnknownSubjectAndAttribute(): void
    {
        $voter = $this->makeVoter();
        $token = $this->createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, new \stdClass(), [MediaVoter::VIEW]));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, new Media(), ['ROLE_ADMIN']));
    }

    public function testAdminBypassRoleGrantsEverything(): void
    {
        $voter = $this->makeVoter(
            isAdmin: true,
            user: $this->createMock(UserInterface::class),
        );
        $media = $this->persistedMedia(id: 7, owner: 'user:someone-else');

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->createMock(TokenInterface::class), $media, [MediaVoter::DELETE])
        );
    }

    public function testOwnerHasFullRights(): void
    {
        $voter = $this->makeVoter(ownerKey: 'user:42', subjectKeys: ['user:42']);
        $media = $this->persistedMedia(id: 1, owner: 'user:42');

        foreach ([MediaVoter::VIEW, MediaVoter::EDIT, MediaVoter::DELETE, MediaVoter::SHARE] as $attr) {
            self::assertSame(
                VoterInterface::ACCESS_GRANTED,
                $voter->vote($this->createMock(TokenInterface::class), $media, [$attr]),
                "expected GRANTED for $attr"
            );
        }
    }

    public function testGlobalMediaIsAccessibleToLoggedInUser(): void
    {
        $voter = $this->makeVoter(
            ownerKey: 'user:9',
            subjectKeys: ['user:9'],
            user: $this->createMock(UserInterface::class),
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->createMock(TokenInterface::class), new Media(), [MediaVoter::VIEW])
        );
    }

    public function testSharedVisibilityGrantsViewButNotEdit(): void
    {
        $voter = $this->makeVoter(
            ownerKey: 'user:9',
            subjectKeys: ['user:9'],
            user: $this->createMock(UserInterface::class),
        );
        $media = $this->persistedMedia(id: 5, owner: 'user:other', visibility: MediaVisibility::SHARED);
        $token = $this->createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $media, [MediaVoter::VIEW]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $media, [MediaVoter::EDIT]));
    }

    public function testAclShareViewOnly(): void
    {
        $share = (new MediaShare())
            ->setTargetType(MediaShare::TARGET_MEDIA)
            ->setTargetId(5)
            ->setSubjectType(MediaShare::SUBJECT_ROLE)
            ->setSubjectKey('ROLE_MARKETING')
            ->setCanEdit(false);

        $voter = $this->makeVoter(
            subjectKeys: ['user:9', 'role:ROLE_MARKETING'],
            user: $this->createMock(UserInterface::class),
            shares: [$share],
        );
        $media = $this->persistedMedia(id: 5, owner: 'user:other');
        $token = $this->createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $media, [MediaVoter::VIEW]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $media, [MediaVoter::EDIT]));
    }

    public function testAclShareCanEdit(): void
    {
        $share = (new MediaShare())
            ->setTargetType(MediaShare::TARGET_MEDIA)
            ->setTargetId(5)
            ->setSubjectType(MediaShare::SUBJECT_USER)
            ->setSubjectKey('9')
            ->setCanEdit(true);

        $voter = $this->makeVoter(
            subjectKeys: ['user:9'],
            user: $this->createMock(UserInterface::class),
            shares: [$share],
        );
        $media = $this->persistedMedia(id: 5, owner: 'user:other');
        $token = $this->createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $media, [MediaVoter::EDIT]));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $media, [MediaVoter::SHARE]));
    }

    public function testAnonymousDeniedOnPrivateForeignMedia(): void
    {
        $voter = $this->makeVoter(); // no owner, no keys, no user
        $media = $this->persistedMedia(id: 5, owner: 'user:other');

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->createMock(TokenInterface::class), $media, [MediaVoter::VIEW])
        );
    }

    // ---------------------------------------------------------------------

    /**
     * @param array<int, string>    $subjectKeys
     * @param array<int, MediaShare> $shares
     */
    private function makeVoter(
        bool $ownershipEnabled = true,
        string $adminRole = 'ROLE_ADMIN',
        ?string $ownerKey = null,
        array $subjectKeys = [],
        ?UserInterface $user = null,
        bool $isAdmin = false,
        array $shares = [],
    ): MediaVoter {
        $provider = $this->createMock(MediaOwnerProviderInterface::class);
        $provider->method('getCurrentOwnerKey')->willReturn($ownerKey);
        $provider->method('getCurrentSubjectKeys')->willReturn($subjectKeys);

        $shareRepo = $this->createMock(MediaShareRepository::class);
        $shareRepo->method('findForTarget')->willReturn($shares);

        return new MediaVoter(
            $ownershipEnabled,
            $adminRole,
            $provider,
            $shareRepo,
            $this->makeSecurity($user, $isAdmin),
        );
    }

    /**
     * Build a real Security helper backed by mocked internal services
     * (Security is final and cannot be mocked directly).
     */
    private function makeSecurity(?UserInterface $user, bool $isAdmin): Security
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($user !== null ? $token : null);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn($isAdmin);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            static fn (string $id): bool => in_array($id, ['security.token_storage', 'security.authorization_checker'], true)
        );
        $container->method('get')->willReturnCallback(
            static fn (string $id) => match ($id) {
                'security.token_storage' => $tokenStorage,
                'security.authorization_checker' => $authChecker,
                default => throw new \LogicException("Unknown service $id"),
            }
        );

        return new Security($container);
    }

    private function persistedMedia(
        int $id,
        ?string $owner,
        MediaVisibility $visibility = MediaVisibility::PRIVATE,
    ): Media {
        $media = (new Media())->setOwner($owner)->setVisibility($visibility);

        $prop = new \ReflectionProperty(Media::class, 'id');
        $prop->setValue($media, $id);

        return $media;
    }
}

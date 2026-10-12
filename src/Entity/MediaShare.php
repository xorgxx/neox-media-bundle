<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Access control entry granting a subject access to a media or a collection.
 *
 * The bundle never knows user/team entities: everything is an opaque key
 * produced by MediaOwnerProviderInterface on the reader side.
 *
 * Examples:
 *  - Share media #12 to user 7:        targetType=media,      targetId=12, subjectType=user,     subjectKey='7'
 *  - Share collection #3 to a role:    targetType=collection, targetId=3,  subjectType=role,     subjectKey='ROLE_MARKETING'
 *  - Share media #12 to a team:        targetType=media,      targetId=12, subjectType=team,     subjectKey='marketing'
 *  - Share collection #3 to everyone:  targetType=collection, targetId=3,  subjectType=everyone, subjectKey='*'
 */
#[ORM\Entity(repositoryClass: \Neox\MediaBundle\Repository\MediaShareRepository::class)]
#[ORM\Table(name: 'neox_media_share')]
#[ORM\UniqueConstraint(name: 'uniq_media_share', columns: ['target_type', 'target_id', 'subject_type', 'subject_key'])]
class MediaShare
{
    public const TARGET_MEDIA = 'media';
    public const TARGET_COLLECTION = 'collection';

    public const SUBJECT_USER = 'user';
    public const SUBJECT_ROLE = 'role';
    public const SUBJECT_TEAM = 'team';
    public const SUBJECT_EVERYONE = 'everyone';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** 'media' or 'collection' */
    #[ORM\Column(name: 'target_type', length: 20)]
    private string $targetType;

    /** Id of the Media or MediaCollection row */
    #[ORM\Column(name: 'target_id')]
    private int $targetId;

    /** 'user' | 'role' | 'team' | 'everyone' */
    #[ORM\Column(name: 'subject_type', length: 20)]
    private string $subjectType;

    /** Opaque subject key: '42', 'ROLE_MARKETING', 'marketing', '*' */
    #[ORM\Column(name: 'subject_key', length: 100)]
    private string $subjectKey;

    #[ORM\Column]
    private bool $canEdit = false;

    #[ORM\Column]
    private bool $canReshare = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTargetType(): string { return $this->targetType; }
    public function setTargetType(string $v): self { $this->targetType = $v; return $this; }
    public function getTargetId(): int { return $this->targetId; }
    public function setTargetId(int $v): self { $this->targetId = $v; return $this; }
    public function getSubjectType(): string { return $this->subjectType; }
    public function setSubjectType(string $v): self { $this->subjectType = $v; return $this; }
    public function getSubjectKey(): string { return $this->subjectKey; }
    public function setSubjectKey(string $v): self { $this->subjectKey = $v; return $this; }
    public function canEdit(): bool { return $this->canEdit; }
    public function setCanEdit(bool $v): self { $this->canEdit = $v; return $this; }
    public function canReshare(): bool { return $this->canReshare; }
    public function setCanReshare(bool $v): self { $this->canReshare = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

<?php

namespace Neox\MediaBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Neox\MediaBundle\Enum\MediaRiskLevel;
use Neox\MediaBundle\Enum\MediaSource;
use Neox\MediaBundle\Enum\MediaStatus;
use Neox\MediaBundle\Enum\MediaType;
use Neox\MediaBundle\Enum\MediaVisibility;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: \Neox\MediaBundle\Repository\MediaRepository::class)]
#[ORM\Table(name: 'neox_media')]
#[Vich\Uploadable]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36, unique: true)]
    private string $uuid;

    #[Vich\UploadableField(
        mapping: 'neox_media',
        fileNameProperty: 'filename',
        size: 'size',
        mimeType: 'mimeType',
        originalName: 'originalName'
    )]
    private ?File $file = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(nullable: true)]
    private ?int $size = null;

    #[ORM\Column(enumType: MediaType::class)]
    private MediaType $type = MediaType::OTHER;

    #[ORM\Column(enumType: MediaSource::class)]
    private MediaSource $source = MediaSource::FILE;

    #[ORM\Column(enumType: MediaStatus::class)]
    private MediaStatus $status = MediaStatus::PENDING;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $embedUrl = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $provider = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $storageKey = null;

    /**
     * Physical subdirectory under the upload destination (e.g. "acme-corp").
     * Set once at upload time by MediaDirectoryProviderInterface.
     * null = stored at the root (flat layout).
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $storageDir = null;

    /**
     * Opaque owner key (e.g. "user:42", "company:acme"). Only meaningful
     * when neox_media.ownership.enabled is true. null = global media.
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $owner = null;

    /**
     * Visibility level used by MediaVoter when ownership is enabled.
     */
    #[ORM\Column(enumType: MediaVisibility::class, options: ['default' => 'private'])]
    private MediaVisibility $visibility = MediaVisibility::PRIVATE;

    #[ORM\Column(nullable: true)]
    private ?int $width = null;

    #[ORM\Column(nullable: true)]
    private ?int $height = null;

    #[ORM\Column(nullable: true)]
    private ?float $duration = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $metadata = [];

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->uuid = self::uuidV4();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function getId(): ?int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getFile(): ?File { return $this->file; }

    public function setFile(?File $file): self
    {
        $this->file = $file;
        if ($file !== null) {
            $this->updatedAt = new DateTimeImmutable();
            $this->source = MediaSource::FILE;
        }
        return $this;
    }

    public function getFilename(): ?string { return $this->filename; }
    public function setFilename(?string $filename): self { $this->filename = $filename; return $this; }
    public function getOriginalName(): ?string { return $this->originalName; }
    public function setOriginalName(?string $v): self { $this->originalName = $v; return $this; }
    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(?string $v): self { $this->mimeType = $v; return $this; }
    public function getSize(): ?int { return $this->size; }
    public function setSize(?int $v): self { $this->size = $v; return $this; }
    public function getType(): MediaType { return $this->type; }
    public function setType(MediaType $v): self { $this->type = $v; return $this; }
    public function getSource(): MediaSource { return $this->source; }
    public function setSource(MediaSource $v): self { $this->source = $v; return $this; }
    public function getStatus(): MediaStatus { return $this->status; }
    public function setStatus(MediaStatus $v): self { $this->status = $v; return $this; }
    public function getRiskLevel(): MediaRiskLevel
    {
        $value = $this->metadata['risk_level'] ?? MediaRiskLevel::SAFE->value;

        return is_string($value) && $value !== ''
            ? (MediaRiskLevel::tryFrom($value) ?? MediaRiskLevel::SAFE)
            : MediaRiskLevel::SAFE;
    }

    public function setRiskLevel(MediaRiskLevel $v): self
    {
        $this->metadata['risk_level'] = $v->value;

        return $this;
    }

    public function getRiskReason(): ?string
    {
        $value = $this->metadata['risk_reason'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function setRiskReason(?string $v): self
    {
        if ($v === null || $v === '') {
            unset($this->metadata['risk_reason']);

            return $this;
        }

        $this->metadata['risk_reason'] = $v;

        return $this;
    }

    public function isSandboxed(): bool
    {
        return ($this->metadata['sandboxed'] ?? false) === true;
    }

    public function setSandboxed(bool $v): self
    {
        if (!$v) {
            unset($this->metadata['sandboxed']);

            return $this;
        }

        $this->metadata['sandboxed'] = true;

        return $this;
    }

    public function approveSandbox(): self
    {
        unset($this->metadata['sandboxed'], $this->metadata['risk_reason']);
        $this->metadata['risk_level'] = MediaRiskLevel::SAFE->value;

        return $this;
    }
    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $v): self { $this->url = $v; return $this; }
    public function getEmbedUrl(): ?string { return $this->embedUrl; }
    public function setEmbedUrl(?string $v): self { $this->embedUrl = $v; return $this; }
    public function getProvider(): ?string { return $this->provider; }
    public function setProvider(?string $v): self { $this->provider = $v; return $this; }
    public function getStorageKey(): ?string { return $this->storageKey; }
    public function setStorageKey(?string $v): self { $this->storageKey = $v; return $this; }
    public function getStorageDir(): ?string { return $this->storageDir; }
    public function setStorageDir(?string $v): self { $this->storageDir = $v; return $this; }
    public function getOwner(): ?string { return $this->owner; }
    public function setOwner(?string $v): self { $this->owner = $v; return $this; }
    public function getVisibility(): MediaVisibility { return $this->visibility; }
    public function setVisibility(MediaVisibility $v): self { $this->visibility = $v; return $this; }
    public function getWidth(): ?int { return $this->width; }
    public function setWidth(?int $v): self { $this->width = $v; return $this; }
    public function getHeight(): ?int { return $this->height; }
    public function setHeight(?int $v): self { $this->height = $v; return $this; }
    public function getDuration(): ?float { return $this->duration; }
    public function setDuration(?float $v): self { $this->duration = $v; return $this; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $v): self { $this->title = $v; return $this; }
    public function getAlt(): ?string { return $this->alt; }
    public function setAlt(?string $v): self { $this->alt = $v; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): self { $this->description = $v; return $this; }
    /** @return array<string, mixed> */
    public function getMetadata(): array { return $this->metadata; }
    /** @param array<string, mixed> $v */
    public function setMetadata(array $v): self { $this->metadata = $v; return $this; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?DateTimeImmutable $value = null): self
    {
        $this->updatedAt = $value ?? new DateTimeImmutable();
        return $this;
    }
}

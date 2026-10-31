<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'neox_media_directory')]
#[ORM\UniqueConstraint(name: 'uniq_neox_media_directory_path', columns: ['path'])]
class MediaDirectory
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 1024)]
    private string $path;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['remove'])]
    private Collection $children;

    public function __construct(string $name, string $path, ?self $parent = null)
    {
        $this->name = $name;
        $this->path = $path;
        $this->parent = $parent;
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getPath(): string { return $this->path; }
    public function getParent(): ?self { return $this->parent; }
    /** @return Collection<int, self> */
    public function getChildren(): Collection { return $this->children; }
}

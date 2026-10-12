<?php

namespace Neox\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Neox\MediaBundle\Enum\MediaVisibility;

#[ORM\Entity(repositoryClass: \Neox\MediaBundle\Repository\MediaCollectionRepository::class)]
#[ORM\Table(name: 'neox_media_collection')]
class MediaCollection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    /** Parent folder in the virtual tree (null = root). */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    /** Opaque owner key (e.g. "user:42"). null = global collection. */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $owner = null;

    #[ORM\Column(enumType: MediaVisibility::class, options: ['default' => 'private'])]
    private MediaVisibility $visibility = MediaVisibility::PRIVATE;

    /** true = smart folder whose content is resolved from `rules`. */
    #[ORM\Column]
    private bool $dynamic = false;

    /**
     * Dynamic filter criteria evaluated by FolderRuleEvaluator.
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON)]
    private array $rules = [];

    /** @var Collection<int, MediaCollectionItem> */
    #[ORM\OneToMany(mappedBy: 'collection', targetEntity: MediaCollectionItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $v): self { $this->parent = $v; return $this; }
    /** @return Collection<int, self> */
    public function getChildren(): Collection { return $this->children; }
    public function getOwner(): ?string { return $this->owner; }
    public function setOwner(?string $v): self { $this->owner = $v; return $this; }
    public function getVisibility(): MediaVisibility { return $this->visibility; }
    public function setVisibility(MediaVisibility $v): self { $this->visibility = $v; return $this; }
    public function isDynamic(): bool { return $this->dynamic; }
    public function setDynamic(bool $v): self { $this->dynamic = $v; return $this; }
    /** @return array<string, mixed> */
    public function getRules(): array { return $this->rules; }
    /** @param array<string, mixed> $v */
    public function setRules(array $v): self { $this->rules = $v; return $this; }

    /** Slash-separated path of this folder in the tree (e.g. "marketing/logos"). */
    public function getPath(): string
    {
        $segments = [$this->name];
        $node = $this->parent;
        while ($node !== null) {
            array_unshift($segments, $node->getName());
            $node = $node->getParent();
        }

        return implode('/', $segments);
    }
    /** @return Collection<int, MediaCollectionItem> */
    public function getItems(): Collection { return $this->items; }

    public function addItem(MediaCollectionItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCollection($this);
        }
        return $this;
    }

    public function removeItem(MediaCollectionItem $item): self
    {
        if ($this->items->removeElement($item) && $item->getCollection() === $this) {
            $item->setCollection(null);
        }
        return $this;
    }
}

<?php

namespace Neox\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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

    /** @var Collection<int, MediaCollectionItem> */
    #[ORM\OneToMany(mappedBy: 'collection', targetEntity: MediaCollectionItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $items;

    public function __construct() { $this->items = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
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

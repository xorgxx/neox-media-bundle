<?php

namespace Neox\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'neox_media_collection_item')]
class MediaCollectionItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MediaCollection $collection = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Media $media = null;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }
    public function getCollection(): ?MediaCollection { return $this->collection; }
    public function setCollection(?MediaCollection $v): self { $this->collection = $v; return $this; }
    public function getMedia(): ?Media { return $this->media; }
    public function setMedia(?Media $v): self { $this->media = $v; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $v): self { $this->position = $v; return $this; }
}

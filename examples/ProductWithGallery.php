<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Neox\MediaBundle\Attribute\MediaField;
use Neox\MediaBundle\Entity\Media;

#[ORM\Entity]
class ProductWithGallery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[MediaField(role: 'cover', accept: ['image/*'])]
    private ?Media $cover = null;

    #[ORM\ManyToMany(targetEntity: Media::class)]
    #[ORM\JoinTable(name: 'product_gallery_media')]
    #[MediaField(role: 'gallery', accept: ['image/*', 'video/*'], multiple: true, sortable: true)]
    private Collection $gallery;

    public function __construct()
    {
        $this->gallery = new ArrayCollection();
    }
}

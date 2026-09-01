<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Neox\MediaBundle\Attribute\MediaField;
use Neox\MediaBundle\Entity\Media;

#[ORM\Entity]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[MediaField(role: 'cover', accept: ['image/*'])]
    private ?Media $cover = null;
}

<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Neox\MediaBundle\Entity\MediaCollection;

/**
 * @extends ServiceEntityRepository<MediaCollection>
 */
final class MediaCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaCollection::class);
    }

    public function findWithItemsAndMedia(int $id): ?MediaCollection
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.items', 'i')
            ->leftJoin('i.media', 'm')
            ->addSelect('i', 'm')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Root collections (no parent) ordered by name — top level of the
     * folder tree.
     *
     * @return MediaCollection[]
     */
    public function findRoots(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parent IS NULL')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

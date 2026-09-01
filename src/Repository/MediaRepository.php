<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Enum\MediaType;

/**
 * @extends ServiceEntityRepository<Media>
 */
final class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /** @return Media[] */
    public function searchLibrary(
        ?string $query = null,
        ?MediaType $type = null,
        int $limit = 60,
        int $offset = 0,
        string $sort = 'updatedAt',
        ?int $collectionId = null,
    ): array {
        $qb = $this->createQueryBuilder('m')
            ->setMaxResults(max(1, min($limit, 200)))
            ->setFirstResult(max(0, $offset));

        $sortMap = [
            'updatedAt' => ['m.updatedAt', 'DESC'],
            'createdAt' => ['m.createdAt', 'ASC'],
            'title' => ['m.title', 'ASC'],
            'size' => ['m.size', 'DESC'],
        ];
        [$sortField, $sortDir] = $sortMap[$sort] ?? $sortMap['updatedAt'];
        $qb->orderBy($sortField, $sortDir);

        if ($query) {
            $qb->andWhere('LOWER(m.title) LIKE :q OR LOWER(m.originalName) LIKE :q OR LOWER(m.filename) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($query).'%');
        }

        if ($type) {
            $qb->andWhere('m.type = :type')->setParameter('type', $type);
        }

        if ($collectionId !== null) {
            $qb->andWhere('m.id IN (SELECT IDENTITY(ci.media) FROM Neox\\MediaBundle\\Entity\\MediaCollectionItem ci WHERE ci.collection = :cid)')
                ->setParameter('cid', $collectionId);
        }

        return $qb->getQuery()
            ->useQueryCache(true)
            ->getResult();
    }

    public function countLibrary(?string $query = null, ?MediaType $type = null, ?int $collectionId = null): int
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)');

        if ($query) {
            $qb->andWhere('LOWER(m.title) LIKE :q OR LOWER(m.originalName) LIKE :q OR LOWER(m.filename) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($query).'%');
        }

        if ($type) {
            $qb->andWhere('m.type = :type')->setParameter('type', $type);
        }

        if ($collectionId !== null) {
            $qb->andWhere('m.id IN (SELECT IDENTITY(ci.media) FROM Neox\\MediaBundle\\Entity\\MediaCollectionItem ci WHERE ci.collection = :cid)')
                ->setParameter('cid', $collectionId);
        }

        return (int) $qb->getQuery()
            ->useQueryCache(true)
            ->getSingleScalarResult();
    }
}

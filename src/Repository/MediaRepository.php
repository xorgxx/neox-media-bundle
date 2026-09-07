<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
        ?bool $sandboxed = null,
    ): array {
        $limit = max(1, min($limit, 200));
        $offset = max(0, $offset);
        $qb = $this->createLibraryQueryBuilder($query, $type, $sort, $collectionId);

        if ($sandboxed === null) {
            return $qb
                ->setMaxResults($limit)
                ->setFirstResult($offset)
                ->getQuery()
                ->useQueryCache(true)
                ->getResult();
        }

        $items = array_values(array_filter(
            $qb->getQuery()->useQueryCache(true)->getResult(),
            static fn (Media $media): bool => $media->isSandboxed() === $sandboxed,
        ));

        return array_slice($items, $offset, $limit);
    }

    public function countLibrary(?string $query = null, ?MediaType $type = null, ?int $collectionId = null, ?bool $sandboxed = null): int
    {
        if ($sandboxed !== null) {
            $items = $this->createLibraryQueryBuilder($query, $type, 'updatedAt', $collectionId)
                ->getQuery()
                ->useQueryCache(true)
                ->getResult();

            return count(array_filter(
                $items,
                static fn (Media $media): bool => $media->isSandboxed() === $sandboxed,
            ));
        }

        $qb = $this->createLibraryQueryBuilder($query, $type, 'updatedAt', $collectionId)
            ->select('COUNT(m.id)');

        return (int) $qb->getQuery()
            ->useQueryCache(true)
            ->getSingleScalarResult();
    }

    public function countSandboxed(): int
    {
        return $this->countLibrary(sandboxed: true);
    }

    private function createLibraryQueryBuilder(
        ?string $query,
        ?MediaType $type,
        string $sort,
        ?int $collectionId,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('m');

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
            $qb->andWhere('m.id IN (SELECT IDENTITY(ci.media) FROM Neox\MediaBundle\Entity\MediaCollectionItem ci WHERE ci.collection = :cid)')
                ->setParameter('cid', $collectionId);
        }

        return $qb;
    }
}

<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Neox\MediaBundle\Entity\Media;
use Neox\MediaBundle\Entity\MediaCollection;
use Neox\MediaBundle\Entity\MediaCollectionItem;
use Neox\MediaBundle\Entity\MediaShare;
use Neox\MediaBundle\Enum\MediaType;
use Neox\MediaBundle\Enum\MediaVisibility;
use Neox\MediaBundle\Folder\FolderRuleEvaluator;

/**
 * @extends ServiceEntityRepository<Media>
 */
final class MediaRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ?FolderRuleEvaluator $ruleEvaluator = null,
    ) {
        parent::__construct($registry, Media::class);
    }

    /**
     * @param array<int, string> $subjectKeys Opaque keys of the current user
     *                                        ('user:42', 'role:ROLE_X', 'team:y').
     *                                        Empty = anonymous / global mode.
     * @param string             $scope       'all' (visible), 'mine' (owned), 'shared' (shared with me)
     *
     * @return Media[]
     */
    public function searchLibrary(
        ?string $query = null,
        ?MediaType $type = null,
        int $limit = 60,
        int $offset = 0,
        string $sort = 'updatedAt',
        ?int $collectionId = null,
        ?bool $sandboxed = null,
        ?string $ownerKey = null,
        array $subjectKeys = [],
        string $scope = 'all',
    ): array {
        $limit = max(1, min($limit, 200));
        $offset = max(0, $offset);
        $qb = $this->createLibraryQueryBuilder($query, $type, $sort, $collectionId, $ownerKey, $subjectKeys, $scope);

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

    /**
     * @param array<int, string> $subjectKeys
     */
    public function countLibrary(
        ?string $query = null,
        ?MediaType $type = null,
        ?int $collectionId = null,
        ?bool $sandboxed = null,
        ?string $ownerKey = null,
        array $subjectKeys = [],
        string $scope = 'all',
    ): int {
        if ($sandboxed !== null) {
            $items = $this->createLibraryQueryBuilder($query, $type, 'updatedAt', $collectionId, $ownerKey, $subjectKeys, $scope)
                ->getQuery()
                ->useQueryCache(true)
                ->getResult();

            return count(array_filter(
                $items,
                static fn (Media $media): bool => $media->isSandboxed() === $sandboxed,
            ));
        }

        $qb = $this->createLibraryQueryBuilder($query, $type, 'updatedAt', $collectionId, $ownerKey, $subjectKeys, $scope)
            ->select('COUNT(m.id)')
            ->resetDQLPart('orderBy');

        return (int) $qb->getQuery()
            ->useQueryCache(true)
            ->getSingleScalarResult();
    }

    /**
     * @param array<int, string> $subjectKeys
     */
    public function countSandboxed(?string $ownerKey = null, array $subjectKeys = []): int
    {
        return $this->countLibrary(sandboxed: true, ownerKey: $ownerKey, subjectKeys: $subjectKeys);
    }

    /**
     * @param array<int, string> $subjectKeys
     */
    private function createLibraryQueryBuilder(
        ?string $query,
        ?MediaType $type,
        string $sort,
        ?int $collectionId,
        ?string $ownerKey,
        array $subjectKeys,
        string $scope,
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
            $this->applyCollection($qb, $collectionId);
        }

        $this->applyScope($qb, $ownerKey, $subjectKeys, $scope);

        return $qb;
    }

    /**
     * Restrict to a collection's members.
     *
     * Dynamic collections resolve their `rules` into DQL criteria;
     * static collections use the materialized MediaCollectionItem join.
     */
    private function applyCollection(QueryBuilder $qb, int $collectionId): void
    {
        $collection = $this->getEntityManager()->find(MediaCollection::class, $collectionId);

        if ($collection instanceof MediaCollection
            && $collection->isDynamic()
            && $collection->getRules() !== []
            && $this->ruleEvaluator !== null
        ) {
            $this->ruleEvaluator->apply($qb, $collection->getRules());

            return;
        }

        $qb->andWhere(
            'm.id IN (SELECT IDENTITY(ci.media) FROM '.MediaCollectionItem::class.' ci WHERE ci.collection = :cid)'
        )->setParameter('cid', $collectionId);
    }

    /**
     * Ownership/visibility/ACL scoping.
     *
     * A media is visible when at least one of:
     *  - it is global (owner IS NULL),
     *  - its visibility is not PRIVATE,
     *  - the current user owns it (owner in subject keys),
     *  - a MediaShare entry grants access.
     *
     * @param array<int, string> $subjectKeys
     */
    private function applyScope(QueryBuilder $qb, ?string $ownerKey, array $subjectKeys, string $scope): void
    {
        // Legacy global mode: no keys, everything visible.
        if ($ownerKey === null && $subjectKeys === [] && $scope === 'all') {
            return;
        }

        $keys = array_values(array_unique(array_filter(array_merge(
            $ownerKey !== null ? [$ownerKey] : [],
            $subjectKeys,
        ))));

        // MediaShare targets the given subject keys can see.
        $sharedSub = 'SELECT s.targetId FROM '.MediaShare::class.' s'
            ." WHERE s.targetType = 'media'"
            ." AND CONCAT(CONCAT(s.subjectType, ':'), s.subjectKey) IN (:subjectKeys)";

        if ($scope === 'mine') {
            if ($keys === []) {
                $qb->andWhere('1 = 0');

                return;
            }
            $qb->andWhere('m.owner IN (:subjectKeys)')->setParameter('subjectKeys', $keys);

            return;
        }

        if ($scope === 'shared') {
            // Shared with me: via ACL or non-private visibility, excluding mine.
            if ($keys === []) {
                $qb->andWhere('1 = 0');

                return;
            }
            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->in('m.id', $sharedSub),
                        'm.visibility != :scopePrivate'
                    ),
                    'm.owner IS NOT NULL',
                    $qb->expr()->notIn('m.owner', ':subjectKeys')
                )
            )
            ->setParameter('subjectKeys', $keys)
            ->setParameter('scopePrivate', MediaVisibility::PRIVATE);

            return;
        }

        // scope 'all'
        if ($keys === []) {
            // Anonymous / no keys: only global and non-private media.
            $qb->andWhere('m.owner IS NULL OR m.visibility != :scopePrivate')
               ->setParameter('scopePrivate', MediaVisibility::PRIVATE);

            return;
        }

        $qb->andWhere(
            $qb->expr()->orX(
                'm.owner IS NULL',
                'm.visibility != :scopePrivate',
                $qb->expr()->in('m.owner', ':subjectKeys'),
                $qb->expr()->in('m.id', $sharedSub)
            )
        )
        ->setParameter('subjectKeys', $keys)
        ->setParameter('scopePrivate', MediaVisibility::PRIVATE);
    }
}

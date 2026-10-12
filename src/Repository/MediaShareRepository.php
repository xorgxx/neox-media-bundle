<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Neox\MediaBundle\Entity\MediaShare;

/**
 * @extends ServiceEntityRepository<MediaShare>
 *
 * Non-final so it can be doubled in unit tests (no bypass-finals dependency).
 */
class MediaShareRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaShare::class);
    }

    /**
     * Find the share entries of a target that match any of the given subject keys.
     *
     * "everyone" shares are always matched.
     *
     * @param array<int, string> $subjectKeys Fully qualified keys like 'user:42', 'role:ROLE_ADMIN', 'team:marketing'
     *
     * @return MediaShare[]
     */
    public function findForTarget(string $targetType, int $targetId, array $subjectKeys): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.targetType = :tt')
            ->andWhere('s.targetId = :ti')
            ->setParameter('tt', $targetType)
            ->setParameter('ti', $targetId);

        $keys = array_merge(['everyone:*'], $subjectKeys);

        $qb->andWhere(
            $qb->expr()->in(
                "CONCAT(CONCAT(s.subjectType, ':'), s.subjectKey)",
                ':keys'
            )
        )->setParameter('keys', $keys);

        return $qb->getQuery()->getResult();
    }

    /**
     * Whether any share grants access to the target for the given subject keys.
     *
     * @param array<int, string> $subjectKeys
     */
    public function grantsAccess(string $targetType, int $targetId, array $subjectKeys, bool $needEdit = false): bool
    {
        $shares = $this->findForTarget($targetType, $targetId, $subjectKeys);

        foreach ($shares as $share) {
            if (!$needEdit || $share->canEdit()) {
                return true;
            }
        }

        return false;
    }

    /**
     * All target ids of a given type shared with the given subject keys.
     *
     * @param array<int, string> $subjectKeys
     *
     * @return array<int, int>
     */
    public function sharedTargetIds(string $targetType, array $subjectKeys): array
    {
        $keys = array_merge(['everyone:*'], $subjectKeys);

        $rows = $this->createQueryBuilder('s')
            ->select('s.targetId')
            ->where('s.targetType = :tt')
            ->setParameter('tt', $targetType)
            ->andWhere(
                $this->createQueryBuilder('x')->expr()->in(
                    "CONCAT(CONCAT(s.subjectType, ':'), s.subjectKey)",
                    ':keys'
                )
            )
            ->setParameter('keys', $keys)
            ->getQuery()
            ->getScalarResult();

        return array_map('intval', array_column($rows, 'targetId'));
    }
}

<?php

namespace App\Repository;

use App\Entity\Trigger as TriggerEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TriggerEntity>
 */
class TriggerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TriggerEntity::class);
    }

    /**
     * Find triggers that need recalculation (status = 'received' and recalculation_date <= now)
     */
    public function findTriggersForRecalculation(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.recalculationDate IS NOT NULL')
            ->andWhere('t.recalculationDate <= :now')
            ->setParameter('status', 'received')
            ->setParameter('now', $now)
            ->orderBy('t.recalculationDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all triggers for an aggregate
     */
    public function findByAggregateId(string $aggregateId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.aggregateId = :aggregateId')
            ->setParameter('aggregateId', $aggregateId)
            ->orderBy('t.receivedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find triggers that should be invalidated when a new trigger arrives
     */
    public function findConflictingTriggers(string $aggregateId, string $triggerName): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.aggregateId = :aggregateId')
            ->andWhere('t.name = :name')
            ->andWhere('t.status = :status')
            ->setParameter('aggregateId', $aggregateId)
            ->setParameter('name', $triggerName)
            ->setParameter('status', 'received')
            ->getQuery()
            ->getResult();
    }
}

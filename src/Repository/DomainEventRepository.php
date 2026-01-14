<?php

namespace App\Repository;

use App\Entity\DomainEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DomainEvent>
 */
class DomainEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainEvent::class);
    }

    public function findByAggregateId(string $aggregateId): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.aggregateId = :aggregateId')
            ->setParameter('aggregateId', $aggregateId)
            ->orderBy('e.sequence', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextSequence(): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('MAX(e.sequence) as maxSequence')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int)$result + 1 : 1;
    }
}

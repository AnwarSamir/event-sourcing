<?php

namespace App\Repository;

use App\Entity\OrderSnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderSnapshot>
 */
class OrderSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderSnapshot::class);
    }

    public function findByOrderId(string $orderId): ?OrderSnapshot
    {
        return $this->createQueryBuilder('s')
            ->where('s.orderId = :orderId')
            ->setParameter('orderId', $orderId)
            ->orderBy('s.version', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\OrderReadModelEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderReadModelEntity>
 */
class OrderReadModelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderReadModelEntity::class);
    }

    public function findByOrderId(string $orderId): ?OrderReadModelEntity
    {
        return $this->find($orderId);
    }

    public function findAllOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(OrderReadModelEntity $entity): void
    {
        $entity->setUpdatedAt(new \DateTimeImmutable());
        $this->getEntityManager()->persist($entity);
        // Note: flush() is handled by the transaction in OrderCommandHandler
    }

    public function delete(string $orderId): void
    {
        $entity = $this->find($orderId);
        if ($entity) {
            $this->getEntityManager()->remove($entity);
            $this->getEntityManager()->flush();
        }
    }
}

<?php

namespace App\Infrastructure\Snapshot;

use App\Domain\Aggregate\OrderSnapshot as DomainSnapshot;
use App\Entity\OrderSnapshot as OrderSnapshotEntity;
use App\Repository\OrderSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;

class DatabaseSnapshotStore implements SnapshotStoreInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderSnapshotRepository $repository
    ) {
    }

    public function save(string $aggregateId, DomainSnapshot $snapshot): void
    {
        // Check if snapshot already exists
        $entity = $this->repository->findByOrderId($aggregateId);

        if ($entity === null) {
            $entity = new OrderSnapshotEntity();
            $entity->setOrderId($aggregateId);
        }

        $entity->setStatus($snapshot->getStatus());
        $entity->setIsReturnable($snapshot->isReturnable());
        $entity->setDeliveredAt($snapshot->getDeliveredAt());
        $entity->setFeedbackWindowEndDate($snapshot->getFeedbackWindowEndDate());
        $entity->setVersion($snapshot->getVersion());

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function get(string $aggregateId): ?DomainSnapshot
    {
        $entity = $this->repository->findByOrderId($aggregateId);

        if ($entity === null) {
            return null;
        }

        return new DomainSnapshot(
            orderId: $entity->getOrderId(),
            status: $entity->getStatus(),
            isReturnable: $entity->isReturnable(),
            deliveredAt: $entity->getDeliveredAt(),
            feedbackWindowEndDate: $entity->getFeedbackWindowEndDate(),
            version: $entity->getVersion()
        );
    }
}

<?php

namespace App\Application\CommandHandler;

use App\Application\Projection\OrderProjection;
use App\Domain\Aggregate\OrderAggregate;
use App\Domain\Aggregate\OrderRepository;
use App\Domain\Command\Command;
use App\Domain\Event\DomainEvent;
use Doctrine\ORM\EntityManagerInterface;

class OrderCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly OrderProjection $projection,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function handle(Command $command): void
    {
        // Begin transaction to ensure atomicity
        $this->entityManager->beginTransaction();

        try {
            // Load or create aggregate
            $aggregate = $this->repository->getById($command->getAggregateId());
            
            if ($aggregate === null) {
                $aggregate = OrderAggregate::create($command->getAggregateId());
            }

            // Handle command (this will raise events)
            $aggregate->handle($command);

            // Get uncommitted events before saving (save will mark them as committed)
            $uncommittedEvents = $aggregate->getUncommittedEvents();

            // Save aggregate (events are persisted, but not flushed yet)
            $this->repository->save($aggregate);

            // Update projection with new events (read model persisted, but not flushed yet)
            foreach ($uncommittedEvents as $event) {
                $this->projection->handle($event);
            }

            // Commit transaction: all changes (events, snapshots, read models) saved atomically
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            // Rollback on any error
            $this->entityManager->rollback();
            throw $e;
        }
    }
}

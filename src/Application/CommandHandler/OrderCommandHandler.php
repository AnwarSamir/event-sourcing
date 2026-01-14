<?php

namespace App\Application\CommandHandler;

use App\Application\Projection\OrderProjection;
use App\Domain\Aggregate\OrderAggregate;
use App\Domain\Aggregate\OrderRepository;
use App\Domain\Command\Command;
use App\Domain\Event\DomainEvent;

class OrderCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly OrderProjection $projection
    ) {
    }

    public function handle(Command $command): void
    {
        // Load or create aggregate
        $aggregate = $this->repository->getById($command->getAggregateId());
        
        if ($aggregate === null) {
            $aggregate = OrderAggregate::create($command->getAggregateId());
        }

        // Handle command (this will raise events)
        $aggregate->handle($command);

        // Get uncommitted events before saving (save will mark them as committed)
        $uncommittedEvents = $aggregate->getUncommittedEvents();

        // Save aggregate (events are persisted)
        $this->repository->save($aggregate);

        // Update projection with new events
        foreach ($uncommittedEvents as $event) {
            $this->projection->handle($event);
        }
    }
}

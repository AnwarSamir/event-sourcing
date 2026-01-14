<?php

namespace App\Application\TriggerHandler;

use App\Domain\Aggregate\OrderAggregate;
use App\Domain\Aggregate\OrderRepository;
use App\Domain\Event\DomainEvent;
use App\Domain\Processor\EventProcessor;
use App\Domain\Processor\TriggerProcessor;
use App\Domain\Trigger\Trigger;
use App\Entity\Trigger as TriggerEntity;
use App\Repository\TriggerRepository;
use Doctrine\ORM\EntityManagerInterface;

class TriggerHandler
{
    public function __construct(
        private readonly TriggerProcessor $triggerProcessor,
        private readonly EventProcessor $eventProcessor,
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TriggerRepository $triggerRepository
    ) {
    }

    public function handleTrigger(Trigger $trigger, string $aggregateId): void
    {
        // Check for conflicting triggers and invalidate them
        $conflictingTriggers = $this->triggerRepository->findConflictingTriggers($aggregateId, $trigger->getName());
        foreach ($conflictingTriggers as $conflicting) {
            $conflicting->markAsInvalidated();
        }

        // Process the trigger
        $result = $this->triggerProcessor->process($trigger, $aggregateId);

        // Persist trigger entity
        $triggerEntity = new TriggerEntity();
        $triggerEntity->setTriggerId($trigger->getId());
        $triggerEntity->setAggregateId($aggregateId);
        $triggerEntity->setName($trigger->getName());
        $triggerEntity->setPayloadArray($trigger->getPayload());
        $triggerEntity->setRecalculationDate($result->getNextRecalculationDate());
        $triggerEntity->setStatus('received');

        $this->entityManager->persist($triggerEntity);

        // If recalculation is needed now, process immediately
        if ($result->shouldRecalculateNow() && $result->hasEvents()) {
            $this->applyEvents($aggregateId, $result->getEvents());
            $triggerEntity->markAsApplied();
        }

        $this->entityManager->flush();
    }

    public function processScheduledRecalculation(\DateTimeImmutable $now): int
    {
        $triggers = $this->triggerRepository->findTriggersForRecalculation($now);
        $processed = 0;

        foreach ($triggers as $triggerEntity) {
            // Re-process the trigger
            $trigger = new Trigger(
                id: $triggerEntity->getTriggerId(),
                name: $triggerEntity->getName(),
                payload: $triggerEntity->getPayloadArray(),
                receivedAt: $triggerEntity->getReceivedAt()
            );

            $result = $this->triggerProcessor->process($trigger, $triggerEntity->getAggregateId());

            if ($result->hasEvents()) {
                $this->applyEvents($triggerEntity->getAggregateId(), $result->getEvents());
                $triggerEntity->markAsApplied();
                $processed++;
            }
        }

        $this->entityManager->flush();

        return $processed;
    }

    private function applyEvents(string $aggregateId, array $events): void
    {
        // Load or create aggregate
        $aggregate = $this->orderRepository->getById($aggregateId);
        
        if ($aggregate === null) {
            $aggregate = OrderAggregate::create($aggregateId);
        }

        // Apply events to aggregate
        foreach ($events as $event) {
            $aggregate->handle($this->eventToCommand($event));
        }

        // Save aggregate
        $this->orderRepository->save($aggregate);
    }

    private function eventToCommand(DomainEvent $event): \App\Domain\Command\Command
    {
        // This is a simplified mapping - in reality you'd have proper command creation logic
        return match ($event->getEventType()) {
            'OrderDelivered' => new \App\Domain\Command\DeliverOrder($event->getAggregateId()),
            'OrderShipped' => new \App\Domain\Command\ShipOrder(
                $event->getAggregateId(),
                $event instanceof \App\Domain\Event\OrderShipped ? $event->getTrackingNumber() : ''
            ),
            default => throw new \RuntimeException('Unknown event type: ' . $event->getEventType())
        };
    }
}

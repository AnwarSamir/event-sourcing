<?php

namespace App\Application\TriggerHandler;

use App\Application\Projection\OrderProjection;
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
        private readonly OrderProjection $projection,
        private readonly EntityManagerInterface $entityManager,
        private readonly TriggerRepository $triggerRepository
    ) {
    }

    public function handleTrigger(Trigger $trigger, string $aggregateId): void
    {
        // Begin transaction to ensure atomicity
        $this->entityManager->beginTransaction();

        try {
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

            // Commit transaction: all changes saved atomically
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            // Rollback on any error
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function processScheduledRecalculation(\DateTimeImmutable $now): int
    {
        // Begin transaction to ensure atomicity
        $this->entityManager->beginTransaction();

        try {
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

            // Commit transaction: all changes saved atomically
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $processed;
        } catch (\Exception $e) {
            // Rollback on any error
            $this->entityManager->rollback();
            throw $e;
        }
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

        // Get uncommitted events before saving
        $uncommittedEvents = $aggregate->getUncommittedEvents();

        // Save aggregate (events persisted, but not flushed yet)
        $this->orderRepository->save($aggregate);

        // Update projection with new events (read model persisted, but not flushed yet)
        foreach ($uncommittedEvents as $event) {
            $this->projection->handle($event);
        }
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

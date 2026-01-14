<?php

namespace App\Domain\Processor;

use App\Domain\Aggregate\OrderAggregate;
use App\Domain\Event\DomainEvent;
use App\Infrastructure\EventStore\EventStoreInterface;
use App\Infrastructure\Snapshot\SnapshotStoreInterface;

class EventProcessor
{
    public function __construct(
        private readonly EventStoreInterface $eventStore,
        private readonly SnapshotStoreInterface $snapshotStore
    ) {
    }

    /**
     * Process all events for an aggregate and return the calculated state
     * 
     * @param string $aggregateId
     * @return array{aggregate: OrderAggregate, nextRecalculationDate: ?\DateTimeImmutable}
     */
    public function processAllEvents(string $aggregateId): array
    {
        // Load aggregate from event stream
        $snapshot = $this->snapshotStore->get($aggregateId);
        $events = $this->eventStore->getEvents($aggregateId);

        if (empty($events) && $snapshot === null) {
            throw new \RuntimeException("Aggregate {$aggregateId} not found");
        }

        $aggregate = OrderAggregate::fromHistory($events, $snapshot);

        // Determine next recalculation date based on aggregate state
        $nextRecalculationDate = $this->calculateNextRecalculationDate($aggregate, $events);

        return [
            'aggregate' => $aggregate,
            'nextRecalculationDate' => $nextRecalculationDate
        ];
    }

    private function calculateNextRecalculationDate(OrderAggregate $aggregate, array $events): ?\DateTimeImmutable
    {
        $today = new \DateTimeImmutable();

        // Example: If order is delivered, next recalculation might be feedback window end
        if ($aggregate->getFeedbackWindowEndDate() !== null) {
            return $aggregate->getFeedbackWindowEndDate();
        }

        // Example: If order is shipped but not delivered, check for scheduled delivery dates
        // This would come from triggers that scheduled future recalculations
        
        return null;
    }
}

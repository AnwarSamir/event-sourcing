<?php

namespace App\Domain\Aggregate;

use App\Infrastructure\EventStore\EventStoreInterface;
use App\Infrastructure\Snapshot\SnapshotStoreInterface;

class OrderRepository
{
    private const SNAPSHOT_INTERVAL = 5;

    public function __construct(
        private readonly EventStoreInterface $eventStore,
        private readonly SnapshotStoreInterface $snapshotStore
    ) {
    }

    public function save(OrderAggregate $aggregate): void
    {
        // Save uncommitted events
        foreach ($aggregate->getUncommittedEvents() as $event) {
            $this->eventStore->append($event);
        }

        $aggregate->markEventsAsCommitted();

        // Create snapshot every 5 events
        // Get event count including the new events we just saved
        $eventCount = count($this->eventStore->getEvents($aggregate->getId()));
        if ($eventCount % self::SNAPSHOT_INTERVAL === 0) {
            $this->snapshotStore->save($aggregate->getId(), $aggregate->createSnapshot());
        }
    }

    public function getById(string $orderId): ?OrderAggregate
    {
        // Try to load from snapshot first
        $snapshot = $this->snapshotStore->get($orderId);
        
        // Get all events for this aggregate
        $events = $this->eventStore->getEvents($orderId);

        if (empty($events) && $snapshot === null) {
            return null;
        }

        // Reconstruct aggregate from snapshot + remaining events
        return OrderAggregate::fromHistory($events, $snapshot);
    }
}

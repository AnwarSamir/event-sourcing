<?php

namespace App\Infrastructure\EventStore;

use App\Domain\Event\DomainEvent;

class InMemoryEventStore implements EventStoreInterface
{
    private array $events = [];
    private int $sequence = 0;

    public function append(DomainEvent $event): void
    {
        $this->events[] = [
            'sequence' => ++$this->sequence,
            'aggregateId' => $event->getAggregateId(),
            'eventType' => $event->getEventType(),
            'event' => $event,
            'occurredOn' => $event->getOccurredOn()
        ];
    }

    public function getEvents(string $aggregateId): array
    {
        $events = [];
        foreach ($this->events as $storedEvent) {
            if ($storedEvent['aggregateId'] === $aggregateId) {
                $events[] = $storedEvent['event'];
            }
        }
        return $events;
    }

    public function getAllEvents(): array
    {
        return array_map(fn($storedEvent) => $storedEvent['event'], $this->events);
    }

    public function getEventCount(string $aggregateId): int
    {
        return count($this->getEvents($aggregateId));
    }
}

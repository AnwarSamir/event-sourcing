<?php

namespace App\Infrastructure\EventStore;

use App\Domain\Event\DomainEvent;

interface EventStoreInterface
{
    public function append(DomainEvent $event): void;
    public function getEvents(string $aggregateId): array;
    public function getAllEvents(): array;
    public function getEventCount(string $aggregateId): int;
}

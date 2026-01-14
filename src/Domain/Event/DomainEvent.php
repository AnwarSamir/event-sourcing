<?php

namespace App\Domain\Event;

interface DomainEvent
{
    public function getAggregateId(): string;
    public function getOccurredOn(): \DateTimeImmutable;
    public function getEventType(): string;
}

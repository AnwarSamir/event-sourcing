<?php

namespace App\Domain\Event;

final class ReturnRequested implements DomainEvent
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $reason,
        private readonly \DateTimeImmutable $occurredOn = new \DateTimeImmutable()
    ) {
    }

    public function getAggregateId(): string
    {
        return $this->orderId;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getEventType(): string
    {
        return 'ReturnRequested';
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}

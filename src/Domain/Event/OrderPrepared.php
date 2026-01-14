<?php

namespace App\Domain\Event;

final class OrderPrepared implements DomainEvent
{
    public function __construct(
        private readonly string $orderId,
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
        return 'OrderPrepared';
    }
}

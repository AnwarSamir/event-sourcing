<?php

namespace App\Domain\Event;

final class OrderShipped implements DomainEvent
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $trackingNumber,
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
        return 'OrderShipped';
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }
}

<?php

namespace App\Domain\Event;

final class FeedbackWindowStarted implements DomainEvent
{
    public function __construct(
        private readonly string $orderId,
        private readonly \DateTimeImmutable $windowStartDate,
        private readonly \DateTimeImmutable $windowEndDate,
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
        return 'FeedbackWindowStarted';
    }

    public function getWindowStartDate(): \DateTimeImmutable
    {
        return $this->windowStartDate;
    }

    public function getWindowEndDate(): \DateTimeImmutable
    {
        return $this->windowEndDate;
    }
}

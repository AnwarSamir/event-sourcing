<?php

namespace App\Domain\Event;

final class OrderCreated implements DomainEvent
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $customerId,
        private readonly array $items,
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
        return 'OrderCreated';
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}

<?php

namespace App\Domain\Event;

final class ItemReceivedBack implements DomainEvent
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $warehouseId,
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
        return 'ItemReceivedBack';
    }

    public function getWarehouseId(): string
    {
        return $this->warehouseId;
    }
}

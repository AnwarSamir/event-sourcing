<?php

namespace App\Domain\Command;

final class ShipOrder implements Command
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $trackingNumber
    ) {
    }

    public function getAggregateId(): string
    {
        return $this->orderId;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }
}

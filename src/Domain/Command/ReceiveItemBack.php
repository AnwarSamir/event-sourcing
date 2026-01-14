<?php

namespace App\Domain\Command;

final class ReceiveItemBack implements Command
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $warehouseId
    ) {
    }

    public function getAggregateId(): string
    {
        return $this->orderId;
    }

    public function getWarehouseId(): string
    {
        return $this->warehouseId;
    }
}

<?php

namespace App\Domain\Command;

final class CreateOrder implements Command
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $customerId,
        private readonly array $items
    ) {
    }

    public function getAggregateId(): string
    {
        return $this->orderId;
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

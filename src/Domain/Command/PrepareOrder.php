<?php

namespace App\Domain\Command;

final class PrepareOrder implements Command
{
    public function __construct(
        private readonly string $orderId
    ) {
    }

    public function getAggregateId(): string
    {
        return $this->orderId;
    }
}

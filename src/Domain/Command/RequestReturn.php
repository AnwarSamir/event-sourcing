<?php

namespace App\Domain\Command;

final class RequestReturn implements Command
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $reason
    ) {
    }

    public function getAggregateId(): string
    {
        return $this->orderId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}

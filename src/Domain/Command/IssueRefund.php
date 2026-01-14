<?php

namespace App\Domain\Command;

final class IssueRefund implements Command
{
    public function __construct(
        private readonly string $orderId,
        private readonly float $amount,
        private readonly string $refundTransactionId
    ) {
    }

    public function getAggregateId(): string
    {
        return $this->orderId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getRefundTransactionId(): string
    {
        return $this->refundTransactionId;
    }
}

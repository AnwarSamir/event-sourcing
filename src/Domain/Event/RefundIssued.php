<?php

namespace App\Domain\Event;

final class RefundIssued implements DomainEvent
{
    public function __construct(
        private readonly string $orderId,
        private readonly float $amount,
        private readonly string $refundTransactionId,
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
        return 'RefundIssued';
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

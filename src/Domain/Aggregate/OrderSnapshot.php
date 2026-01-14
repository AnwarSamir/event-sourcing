<?php

namespace App\Domain\Aggregate;

final class OrderSnapshot
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $status,
        private readonly bool $isReturnable,
        private readonly ?\DateTimeImmutable $deliveredAt,
        private readonly ?\DateTimeImmutable $feedbackWindowEndDate,
        private readonly int $version
    ) {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isReturnable(): bool
    {
        return $this->isReturnable;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function getFeedbackWindowEndDate(): ?\DateTimeImmutable
    {
        return $this->feedbackWindowEndDate;
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}

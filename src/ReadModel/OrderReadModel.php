<?php

namespace App\ReadModel;

class OrderReadModel
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $customerId,
        private readonly string $status,
        private readonly bool $isReturnable,
        private readonly ?\DateTimeImmutable $deliveredAt,
        private readonly ?\DateTimeImmutable $feedbackWindowEndDate,
        private readonly ?string $trackingNumber = null,
        private readonly ?string $returnReason = null,
        private readonly ?float $refundAmount = null,
        private readonly array $items = []
    ) {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
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

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function getReturnReason(): ?string
    {
        return $this->returnReason;
    }

    public function getRefundAmount(): ?float
    {
        return $this->refundAmount;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return [
            'orderId' => $this->orderId,
            'customerId' => $this->customerId,
            'status' => $this->status,
            'isReturnable' => $this->isReturnable,
            'deliveredAt' => $this->deliveredAt?->format('Y-m-d H:i:s'),
            'feedbackWindowEndDate' => $this->feedbackWindowEndDate?->format('Y-m-d H:i:s'),
            'trackingNumber' => $this->trackingNumber,
            'returnReason' => $this->returnReason,
            'refundAmount' => $this->refundAmount,
            'items' => $this->items,
        ];
    }
}

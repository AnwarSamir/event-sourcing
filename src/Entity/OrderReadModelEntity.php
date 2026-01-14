<?php

namespace App\Entity;

use App\Repository\OrderReadModelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderReadModelRepository::class)]
#[ORM\Table(name: 'order_read_models')]
#[ORM\Index(columns: ['order_id'], name: 'idx_read_model_order_id')]
#[ORM\Index(columns: ['customer_id'], name: 'idx_read_model_customer_id')]
#[ORM\Index(columns: ['status'], name: 'idx_read_model_status')]
class OrderReadModelEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $orderId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $customerId;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $status;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isReturnable = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $feedbackWindowEndDate = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $trackingNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $returnReason = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $refundAmount = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $items; // JSON encoded

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): self
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isReturnable(): bool
    {
        return $this->isReturnable;
    }

    public function setIsReturnable(bool $isReturnable): self
    {
        $this->isReturnable = $isReturnable;
        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): self
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    public function getFeedbackWindowEndDate(): ?\DateTimeImmutable
    {
        return $this->feedbackWindowEndDate;
    }

    public function setFeedbackWindowEndDate(?\DateTimeImmutable $feedbackWindowEndDate): self
    {
        $this->feedbackWindowEndDate = $feedbackWindowEndDate;
        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): self
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    public function getReturnReason(): ?string
    {
        return $this->returnReason;
    }

    public function setReturnReason(?string $returnReason): self
    {
        $this->returnReason = $returnReason;
        return $this;
    }

    public function getRefundAmount(): ?string
    {
        return $this->refundAmount;
    }

    public function setRefundAmount(?string $refundAmount): self
    {
        $this->refundAmount = $refundAmount;
        return $this;
    }

    public function getItems(): array
    {
        return json_decode($this->items, true) ?? [];
    }

    public function setItems(array $items): self
    {
        $this->items = json_encode($items);
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\OrderSnapshotRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderSnapshotRepository::class)]
#[ORM\Table(name: 'order_snapshots')]
#[ORM\Index(columns: ['order_id'], name: 'idx_order_id')]
class OrderSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $orderId;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $status;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isReturnable;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $feedbackWindowEndDate = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $version;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}

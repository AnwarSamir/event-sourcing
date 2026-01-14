<?php

namespace App\Entity;

use App\Repository\TriggerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TriggerRepository::class)]
#[ORM\Table(name: 'triggers')]
#[ORM\Index(columns: ['aggregate_id'], name: 'idx_trigger_aggregate_id')]
#[ORM\Index(columns: ['name'], name: 'idx_trigger_name')]
#[ORM\Index(columns: ['status'], name: 'idx_trigger_status')]
#[ORM\Index(columns: ['recalculation_date'], name: 'idx_recalculation_date')]
class Trigger
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $triggerId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $aggregateId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    private string $payload;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $status = 'received'; // received, applied, invalidated

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $recalculationDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $receivedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $appliedAt = null;

    public function __construct()
    {
        $this->receivedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTriggerId(): string
    {
        return $this->triggerId;
    }

    public function setTriggerId(string $triggerId): self
    {
        $this->triggerId = $triggerId;
        return $this;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function setAggregateId(string $aggregateId): self
    {
        $this->aggregateId = $aggregateId;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function setPayload(string $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    public function getPayloadArray(): array
    {
        return json_decode($this->payload, true, 512, JSON_THROW_ON_ERROR);
    }

    public function setPayloadArray(array $payload): self
    {
        $this->payload = json_encode($payload, JSON_THROW_ON_ERROR);
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

    public function getRecalculationDate(): ?\DateTimeImmutable
    {
        return $this->recalculationDate;
    }

    public function setRecalculationDate(?\DateTimeImmutable $recalculationDate): self
    {
        $this->recalculationDate = $recalculationDate;
        return $this;
    }

    public function getReceivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(\DateTimeImmutable $receivedAt): self
    {
        $this->receivedAt = $receivedAt;
        return $this;
    }

    public function getAppliedAt(): ?\DateTimeImmutable
    {
        return $this->appliedAt;
    }

    public function setAppliedAt(?\DateTimeImmutable $appliedAt): self
    {
        $this->appliedAt = $appliedAt;
        return $this;
    }

    public function markAsApplied(): self
    {
        $this->status = 'applied';
        $this->appliedAt = new \DateTimeImmutable();
        return $this;
    }

    public function markAsInvalidated(): self
    {
        $this->status = 'invalidated';
        return $this;
    }
}

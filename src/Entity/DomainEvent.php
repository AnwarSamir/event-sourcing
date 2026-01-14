<?php

namespace App\Entity;

use App\Repository\DomainEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DomainEventRepository::class)]
#[ORM\Table(name: 'domain_events')]
#[ORM\Index(columns: ['aggregate_id'], name: 'idx_aggregate_id')]
#[ORM\Index(columns: ['event_type'], name: 'idx_event_type')]
#[ORM\Index(columns: ['occurred_on'], name: 'idx_occurred_on')]
class DomainEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $aggregateId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $eventType;

    #[ORM\Column(type: Types::TEXT)]
    private string $eventData;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $occurredOn;

    #[ORM\Column(type: Types::INTEGER)]
    private int $sequence;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getEventData(): string
    {
        return $this->eventData;
    }

    public function setEventData(string $eventData): self
    {
        $this->eventData = $eventData;
        return $this;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function setOccurredOn(\DateTimeImmutable $occurredOn): self
    {
        $this->occurredOn = $occurredOn;
        return $this;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): self
    {
        $this->sequence = $sequence;
        return $this;
    }
}

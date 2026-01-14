<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Table(name: 'activity_con_pay')]
#[ORM\Index(columns: ['activity_id'], name: 'idx_activity_id')]
#[ORM\Index(columns: ['date'], name: 'idx_activity_date')]
#[ORM\Index(columns: ['source'], name: 'idx_activity_source')]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $activityId;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $source;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $applied = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $invalidated = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $appliedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActivityId(): string
    {
        return $this->activityId;
    }

    public function setActivityId(string $activityId): self
    {
        $this->activityId = $activityId;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function isApplied(): bool
    {
        return $this->applied;
    }

    public function setApplied(bool $applied): self
    {
        $this->applied = $applied;
        if ($applied) {
            $this->appliedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function isInvalidated(): bool
    {
        return $this->invalidated;
    }

    public function setInvalidated(bool $invalidated): self
    {
        $this->invalidated = $invalidated;
        return $this;
    }

    public function getAppliedAt(): ?\DateTimeImmutable
    {
        return $this->appliedAt;
    }
}

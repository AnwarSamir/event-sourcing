<?php

namespace App\Domain\Trigger;

use App\Domain\Event\DomainEvent;

final class TriggerProcessingResult
{
    /**
     * @param DomainEvent[] $events
     */
    public function __construct(
        private readonly array $events,
        private readonly ?\DateTimeImmutable $nextRecalculationDate = null,
        private readonly bool $recalculateNow = false
    ) {
    }

    /**
     * @return DomainEvent[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function getNextRecalculationDate(): ?\DateTimeImmutable
    {
        return $this->nextRecalculationDate;
    }

    public function shouldRecalculateNow(): bool
    {
        return $this->recalculateNow;
    }

    public function hasEvents(): bool
    {
        return !empty($this->events);
    }
}

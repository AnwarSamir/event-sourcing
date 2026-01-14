<?php

namespace App\Domain\Processor;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\OrderDelivered;
use App\Domain\Event\OrderShipped;
use App\Domain\Trigger\Trigger;
use App\Domain\Trigger\TriggerProcessingResult;

class TriggerProcessor
{
    public function process(Trigger $trigger, string $aggregateId): TriggerProcessingResult
    {
        $today = new \DateTimeImmutable();
        $events = [];
        $nextRecalculationDate = null;
        $recalculateNow = false;

        match ($trigger->getName()) {
            'order_update_delivery_date' => $this->processDeliveryDateUpdate($trigger, $aggregateId, $today, $events, $nextRecalculationDate, $recalculateNow),
            'order_update_amount' => $this->processAmountUpdate($trigger, $aggregateId, $today, $events, $nextRecalculationDate, $recalculateNow),
            'order_update_payment_schedule' => $this->processPaymentScheduleUpdate($trigger, $aggregateId, $today, $events, $nextRecalculationDate, $recalculateNow),
            default => null
        };

        return new TriggerProcessingResult($events, $nextRecalculationDate, $recalculateNow);
    }

    private function processDeliveryDateUpdate(
        Trigger $trigger,
        string $aggregateId,
        \DateTimeImmutable $today,
        array &$events,
        ?\DateTimeImmutable &$nextRecalculationDate,
        bool &$recalculateNow
    ): void {
        $newDeliveryDate = \DateTimeImmutable::createFromFormat('d.m.Y', $trigger->getPayloadValue('delivery_date'));
        
        if ($newDeliveryDate === false) {
            return;
        }

        // If delivery date is in the future, schedule recalculation
        if ($newDeliveryDate > $today) {
            $nextRecalculationDate = $newDeliveryDate;
            $recalculateNow = false;
            // Don't create event now, wait for scheduled recalculation
        } else {
            // Delivery date is today or in the past, process immediately
            $events[] = new OrderDelivered($aggregateId, $today);
            $recalculateNow = true;
        }
    }

    private function processAmountUpdate(
        Trigger $trigger,
        string $aggregateId,
        \DateTimeImmutable $today,
        array &$events,
        ?\DateTimeImmutable &$nextRecalculationDate,
        bool &$recalculateNow
    ): void {
        // Amount changes always require immediate recalculation
        // This would create an AmountUpdated event
        $recalculateNow = true;
        // Note: You'd need to create an AmountUpdated event class
    }

    private function processPaymentScheduleUpdate(
        Trigger $trigger,
        string $aggregateId,
        \DateTimeImmutable $today,
        array &$events,
        ?\DateTimeImmutable &$nextRecalculationDate,
        bool &$recalculateNow
    ): void {
        $scheduleDate = \DateTimeImmutable::createFromFormat('d.m.Y', $trigger->getPayloadValue('schedule_date'));
        
        if ($scheduleDate === false) {
            return;
        }

        // If schedule date is in the future, schedule recalculation
        if ($scheduleDate > $today) {
            $nextRecalculationDate = $scheduleDate;
            $recalculateNow = false;
        } else {
            // Schedule date is today or past, process immediately
            $recalculateNow = true;
        }
    }
}

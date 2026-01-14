<?php

namespace App\Domain\Aggregate;

use App\Domain\Command\Command;
use App\Domain\Command\CreateOrder;
use App\Domain\Command\DeliverOrder;
use App\Domain\Command\IssueRefund;
use App\Domain\Command\PrepareOrder;
use App\Domain\Command\ReceiveItemBack;
use App\Domain\Command\RequestReturn;
use App\Domain\Command\ShipOrder;
use App\Domain\Command\StartFeedbackWindow;
use App\Domain\Event\DomainEvent;
use App\Domain\Event\FeedbackWindowStarted;
use App\Domain\Event\ItemReceivedBack;
use App\Domain\Event\OrderCreated;
use App\Domain\Event\OrderDelivered;
use App\Domain\Event\OrderPrepared;
use App\Domain\Event\OrderShipped;
use App\Domain\Event\RefundIssued;
use App\Domain\Event\ReturnRequested;

class OrderAggregate
{
    private string $orderId;
    private string $status;
    private bool $isReturnable = false;
    private ?\DateTimeImmutable $deliveredAt = null;
    private ?\DateTimeImmutable $feedbackWindowEndDate = null;
    private int $version = 0;
    private array $uncommittedEvents = [];

    private function __construct(string $orderId)
    {
        $this->orderId = $orderId;
        $this->status = 'CREATED';
    }

    public static function create(string $orderId): self
    {
        return new self($orderId);
    }

    public static function fromHistory(array $events, ?OrderSnapshot $snapshot = null): self
    {
        $aggregate = new self('');
        
        // If snapshot exists, restore from it
        if ($snapshot !== null) {
            $aggregate->orderId = $snapshot->getOrderId();
            $aggregate->status = $snapshot->getStatus();
            $aggregate->isReturnable = $snapshot->isReturnable();
            $aggregate->deliveredAt = $snapshot->getDeliveredAt();
            $aggregate->feedbackWindowEndDate = $snapshot->getFeedbackWindowEndDate();
            $aggregate->version = $snapshot->getVersion();
        }

        // Replay events from snapshot version onwards
        // We need to skip events that were already included in the snapshot
        $snapshotVersion = $snapshot ? $snapshot->getVersion() : 0;
        $eventIndex = 0;
        foreach ($events as $event) {
            // Skip events that were already applied in the snapshot
            if ($eventIndex >= $snapshotVersion) {
                $aggregate->apply($event);
                $aggregate->version++;
            }
            $eventIndex++;
        }

        return $aggregate;
    }

    public function handle(Command $command): void
    {
        match (true) {
            $command instanceof CreateOrder => $this->handleCreateOrder($command),
            $command instanceof PrepareOrder => $this->handlePrepareOrder($command),
            $command instanceof ShipOrder => $this->handleShipOrder($command),
            $command instanceof DeliverOrder => $this->handleDeliverOrder($command),
            $command instanceof StartFeedbackWindow => $this->handleStartFeedbackWindow($command),
            $command instanceof RequestReturn => $this->handleRequestReturn($command),
            $command instanceof ReceiveItemBack => $this->handleReceiveItemBack($command),
            $command instanceof IssueRefund => $this->handleIssueRefund($command),
            default => throw new \InvalidArgumentException('Unknown command: ' . get_class($command))
        };
    }

    private function handleCreateOrder(CreateOrder $command): void
    {
        $this->raiseEvent(new OrderCreated(
            $command->getAggregateId(),
            $command->getCustomerId(),
            $command->getItems()
        ));
    }

    private function handlePrepareOrder(PrepareOrder $command): void
    {
        if ($this->status !== 'CREATED') {
            throw new \DomainException('Order must be in CREATED status to be prepared');
        }

        $this->raiseEvent(new OrderPrepared($command->getAggregateId()));
    }

    private function handleShipOrder(ShipOrder $command): void
    {
        if ($this->status !== 'PREPARED') {
            throw new \DomainException('Order must be in PREPARED status to be shipped');
        }

        $this->raiseEvent(new OrderShipped(
            $command->getAggregateId(),
            $command->getTrackingNumber()
        ));
    }

    private function handleDeliverOrder(DeliverOrder $command): void
    {
        if ($this->status !== 'SHIPPED') {
            throw new \DomainException('Order must be in SHIPPED status to be delivered');
        }

        $this->raiseEvent(new OrderDelivered($command->getAggregateId()));
    }

    private function handleStartFeedbackWindow(StartFeedbackWindow $command): void
    {
        if ($this->status !== 'DELIVERED') {
            throw new \DomainException('Order must be DELIVERED to start feedback window');
        }

        $windowStartDate = new \DateTimeImmutable();
        $windowEndDate = $windowStartDate->modify('+14 days');

        $this->raiseEvent(new FeedbackWindowStarted(
            $command->getAggregateId(),
            $windowStartDate,
            $windowEndDate
        ));
    }

    private function handleRequestReturn(RequestReturn $command): void
    {
        // Validation: Order must be delivered
        if ($this->deliveredAt === null) {
            throw new \DomainException('Cannot request return: Order has not been delivered yet');
        }

        // Validation: 14-day window must not have expired
        if ($this->feedbackWindowEndDate === null) {
            throw new \DomainException('Cannot request return: Feedback window has not started');
        }

        $now = new \DateTimeImmutable();
        if ($now > $this->feedbackWindowEndDate) {
            throw new \DomainException('Cannot request return: 14-day return window has expired');
        }

        if (!$this->isReturnable) {
            throw new \DomainException('Order is not in a returnable state');
        }

        $this->raiseEvent(new ReturnRequested(
            $command->getAggregateId(),
            $command->getReason()
        ));
    }

    private function handleReceiveItemBack(ReceiveItemBack $command): void
    {
        if ($this->status !== 'RETURN_REQUESTED') {
            throw new \DomainException('Order must have RETURN_REQUESTED status to receive item back');
        }

        $this->raiseEvent(new ItemReceivedBack(
            $command->getAggregateId(),
            $command->getWarehouseId()
        ));
    }

    private function handleIssueRefund(IssueRefund $command): void
    {
        if ($this->status !== 'ITEM_RECEIVED_BACK') {
            throw new \DomainException('Order must have ITEM_RECEIVED_BACK status to issue refund');
        }

        $this->raiseEvent(new RefundIssued(
            $command->getAggregateId(),
            $command->getAmount(),
            $command->getRefundTransactionId()
        ));
    }

    private function raiseEvent(DomainEvent $event): void
    {
        $this->apply($event);
        $this->uncommittedEvents[] = $event;
        $this->version++;
    }

    private function apply(DomainEvent $event): void
    {
        match ($event->getEventType()) {
            'OrderCreated' => $this->applyOrderCreated($event),
            'OrderPrepared' => $this->applyOrderPrepared($event),
            'OrderShipped' => $this->applyOrderShipped($event),
            'OrderDelivered' => $this->applyOrderDelivered($event),
            'FeedbackWindowStarted' => $this->applyFeedbackWindowStarted($event),
            'ReturnRequested' => $this->applyReturnRequested($event),
            'ItemReceivedBack' => $this->applyItemReceivedBack($event),
            'RefundIssued' => $this->applyRefundIssued($event),
            default => throw new \InvalidArgumentException('Unknown event type: ' . $event->getEventType())
        };
    }

    private function applyOrderCreated(OrderCreated $event): void
    {
        $this->orderId = $event->getAggregateId();
        $this->status = 'CREATED';
    }

    private function applyOrderPrepared(OrderPrepared $event): void
    {
        $this->status = 'PREPARED';
    }

    private function applyOrderShipped(OrderShipped $event): void
    {
        $this->status = 'SHIPPED';
    }

    private function applyOrderDelivered(OrderDelivered $event): void
    {
        $this->status = 'DELIVERED';
        $this->deliveredAt = $event->getOccurredOn();
    }

    private function applyFeedbackWindowStarted(FeedbackWindowStarted $event): void
    {
        $this->isReturnable = true;
        $this->feedbackWindowEndDate = $event->getWindowEndDate();
    }

    private function applyReturnRequested(ReturnRequested $event): void
    {
        $this->status = 'RETURN_REQUESTED';
        $this->isReturnable = false;
    }

    private function applyItemReceivedBack(ItemReceivedBack $event): void
    {
        $this->status = 'ITEM_RECEIVED_BACK';
    }

    private function applyRefundIssued(RefundIssued $event): void
    {
        $this->status = 'REFUNDED';
    }

    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    public function markEventsAsCommitted(): void
    {
        $this->uncommittedEvents = [];
    }

    public function getId(): string
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

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function getFeedbackWindowEndDate(): ?\DateTimeImmutable
    {
        return $this->feedbackWindowEndDate;
    }

    public function createSnapshot(): OrderSnapshot
    {
        return new OrderSnapshot(
            $this->orderId,
            $this->status,
            $this->isReturnable,
            $this->deliveredAt,
            $this->feedbackWindowEndDate,
            $this->version
        );
    }
}

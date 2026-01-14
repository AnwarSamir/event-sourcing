<?php

namespace App\Application\Projection;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\FeedbackWindowStarted;
use App\Domain\Event\ItemReceivedBack;
use App\Domain\Event\OrderCreated;
use App\Domain\Event\OrderDelivered;
use App\Domain\Event\OrderPrepared;
use App\Domain\Event\OrderShipped;
use App\Domain\Event\RefundIssued;
use App\Domain\Event\ReturnRequested;
use App\Entity\OrderReadModelEntity;
use App\ReadModel\OrderReadModel;
use App\Repository\OrderReadModelRepository;

class OrderProjection
{
    public function __construct(
        private readonly OrderReadModelRepository $repository
    ) {
    }

    public function handle(DomainEvent $event): void
    {
        $orderId = $event->getAggregateId();
        
        // Get or create read model entity from database
        $entity = $this->repository->findByOrderId($orderId);
        $readModel = $entity ? $this->entityToReadModel($entity) : null;

        $readModel = match ($event->getEventType()) {
            'OrderCreated' => $this->applyOrderCreated($event),
            'OrderPrepared' => $this->applyOrderPrepared($readModel, $event),
            'OrderShipped' => $this->applyOrderShipped($readModel, $event),
            'OrderDelivered' => $this->applyOrderDelivered($readModel, $event),
            'FeedbackWindowStarted' => $this->applyFeedbackWindowStarted($readModel, $event),
            'ReturnRequested' => $this->applyReturnRequested($readModel, $event),
            'ItemReceivedBack' => $this->applyItemReceivedBack($readModel, $event),
            'RefundIssued' => $this->applyRefundIssued($readModel, $event),
            default => $readModel
        };

        if ($readModel !== null) {
            $this->saveReadModel($readModel);
        }
    }

    private function saveReadModel(OrderReadModel $readModel): void
    {
        $entity = $this->repository->findByOrderId($readModel->getOrderId());
        
        if ($entity === null) {
            $entity = new OrderReadModelEntity();
            $entity->setOrderId($readModel->getOrderId());
        }

        $entity->setCustomerId($readModel->getCustomerId());
        $entity->setStatus($readModel->getStatus());
        $entity->setIsReturnable($readModel->isReturnable());
        $entity->setDeliveredAt($readModel->getDeliveredAt());
        $entity->setFeedbackWindowEndDate($readModel->getFeedbackWindowEndDate());
        $entity->setTrackingNumber($readModel->getTrackingNumber());
        $entity->setReturnReason($readModel->getReturnReason());
        $entity->setRefundAmount($readModel->getRefundAmount() !== null ? (string)$readModel->getRefundAmount() : null);
        $entity->setItems($readModel->getItems());

        $this->repository->save($entity);
    }

    private function entityToReadModel(OrderReadModelEntity $entity): OrderReadModel
    {
        return new OrderReadModel(
            orderId: $entity->getOrderId(),
            customerId: $entity->getCustomerId(),
            status: $entity->getStatus(),
            isReturnable: $entity->isReturnable(),
            deliveredAt: $entity->getDeliveredAt(),
            feedbackWindowEndDate: $entity->getFeedbackWindowEndDate(),
            trackingNumber: $entity->getTrackingNumber(),
            returnReason: $entity->getReturnReason(),
            refundAmount: $entity->getRefundAmount() !== null ? (float)$entity->getRefundAmount() : null,
            items: $entity->getItems()
        );
    }

    private function applyOrderCreated(OrderCreated $event): OrderReadModel
    {
        return new OrderReadModel(
            orderId: $event->getAggregateId(),
            customerId: $event->getCustomerId(),
            status: 'CREATED',
            isReturnable: false,
            deliveredAt: null,
            feedbackWindowEndDate: null,
            trackingNumber: null,
            returnReason: null,
            refundAmount: null,
            items: $event->getItems()
        );
    }

    private function applyOrderPrepared(?OrderReadModel $readModel, OrderPrepared $event): ?OrderReadModel
    {
        if ($readModel === null) {
            return null;
        }

        return new OrderReadModel(
            orderId: $readModel->getOrderId(),
            customerId: $readModel->getCustomerId(),
            status: 'PREPARED',
            isReturnable: $readModel->isReturnable(),
            deliveredAt: $readModel->getDeliveredAt(),
            feedbackWindowEndDate: $readModel->getFeedbackWindowEndDate(),
            trackingNumber: $readModel->getTrackingNumber(),
            returnReason: $readModel->getReturnReason(),
            refundAmount: $readModel->getRefundAmount(),
            items: $readModel->getItems()
        );
    }

    private function applyOrderShipped(?OrderReadModel $readModel, OrderShipped $event): ?OrderReadModel
    {
        if ($readModel === null) {
            return null;
        }

        return new OrderReadModel(
            orderId: $readModel->getOrderId(),
            customerId: $readModel->getCustomerId(),
            status: 'SHIPPED',
            isReturnable: $readModel->isReturnable(),
            deliveredAt: $readModel->getDeliveredAt(),
            feedbackWindowEndDate: $readModel->getFeedbackWindowEndDate(),
            trackingNumber: $event->getTrackingNumber(),
            returnReason: $readModel->getReturnReason(),
            refundAmount: $readModel->getRefundAmount(),
            items: $readModel->getItems()
        );
    }

    private function applyOrderDelivered(?OrderReadModel $readModel, OrderDelivered $event): ?OrderReadModel
    {
        if ($readModel === null) {
            return null;
        }

        return new OrderReadModel(
            orderId: $readModel->getOrderId(),
            customerId: $readModel->getCustomerId(),
            status: 'DELIVERED',
            isReturnable: $readModel->isReturnable(),
            deliveredAt: $event->getOccurredOn(),
            feedbackWindowEndDate: $readModel->getFeedbackWindowEndDate(),
            trackingNumber: $readModel->getTrackingNumber(),
            returnReason: $readModel->getReturnReason(),
            refundAmount: $readModel->getRefundAmount(),
            items: $readModel->getItems()
        );
    }

    private function applyFeedbackWindowStarted(?OrderReadModel $readModel, FeedbackWindowStarted $event): ?OrderReadModel
    {
        if ($readModel === null) {
            return null;
        }

        return new OrderReadModel(
            orderId: $readModel->getOrderId(),
            customerId: $readModel->getCustomerId(),
            status: $readModel->getStatus(),
            isReturnable: true,
            deliveredAt: $readModel->getDeliveredAt(),
            feedbackWindowEndDate: $event->getWindowEndDate(),
            trackingNumber: $readModel->getTrackingNumber(),
            returnReason: $readModel->getReturnReason(),
            refundAmount: $readModel->getRefundAmount(),
            items: $readModel->getItems()
        );
    }

    private function applyReturnRequested(?OrderReadModel $readModel, ReturnRequested $event): ?OrderReadModel
    {
        if ($readModel === null) {
            return null;
        }

        return new OrderReadModel(
            orderId: $readModel->getOrderId(),
            customerId: $readModel->getCustomerId(),
            status: 'RETURN_REQUESTED',
            isReturnable: false,
            deliveredAt: $readModel->getDeliveredAt(),
            feedbackWindowEndDate: $readModel->getFeedbackWindowEndDate(),
            trackingNumber: $readModel->getTrackingNumber(),
            returnReason: $event->getReason(),
            refundAmount: $readModel->getRefundAmount(),
            items: $readModel->getItems()
        );
    }

    private function applyItemReceivedBack(?OrderReadModel $readModel, ItemReceivedBack $event): ?OrderReadModel
    {
        if ($readModel === null) {
            return null;
        }

        return new OrderReadModel(
            orderId: $readModel->getOrderId(),
            customerId: $readModel->getCustomerId(),
            status: 'ITEM_RECEIVED_BACK',
            isReturnable: $readModel->isReturnable(),
            deliveredAt: $readModel->getDeliveredAt(),
            feedbackWindowEndDate: $readModel->getFeedbackWindowEndDate(),
            trackingNumber: $readModel->getTrackingNumber(),
            returnReason: $readModel->getReturnReason(),
            refundAmount: $readModel->getRefundAmount(),
            items: $readModel->getItems()
        );
    }

    private function applyRefundIssued(?OrderReadModel $readModel, RefundIssued $event): ?OrderReadModel
    {
        if ($readModel === null) {
            return null;
        }

        return new OrderReadModel(
            orderId: $readModel->getOrderId(),
            customerId: $readModel->getCustomerId(),
            status: 'REFUNDED',
            isReturnable: false,
            deliveredAt: $readModel->getDeliveredAt(),
            feedbackWindowEndDate: $readModel->getFeedbackWindowEndDate(),
            trackingNumber: $readModel->getTrackingNumber(),
            returnReason: $readModel->getReturnReason(),
            refundAmount: $event->getAmount(),
            items: $readModel->getItems()
        );
    }

    public function get(string $orderId): ?OrderReadModel
    {
        $entity = $this->repository->findByOrderId($orderId);
        return $entity ? $this->entityToReadModel($entity) : null;
    }

    public function getAll(): array
    {
        $entities = $this->repository->findAllOrders();
        return array_map(
            fn($entity) => $this->entityToReadModel($entity),
            $entities
        );
    }
}

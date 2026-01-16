<?php

namespace App\Infrastructure\EventStore;

use App\Domain\Event\DomainEvent;
use App\Entity\DomainEvent as DomainEventEntity;
use App\Repository\DomainEventRepository;
use Doctrine\ORM\EntityManagerInterface;

class DatabaseEventStore implements EventStoreInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DomainEventRepository $repository
    ) {
    }

    public function append(DomainEvent $event): void
    {
        $entity = new DomainEventEntity();
        $entity->setAggregateId($event->getAggregateId());
        $entity->setEventType($event->getEventType());
        $entity->setEventData($this->serializeEvent($event));
        $entity->setOccurredOn($event->getOccurredOn());
        $entity->setSequence($this->repository->getNextSequence());

        $this->entityManager->persist($entity);
        // Note: flush() is handled by the transaction in OrderCommandHandler
    }

    public function getEvents(string $aggregateId): array
    {
        $entities = $this->repository->findByAggregateId($aggregateId);
        
        return array_map(
            fn(DomainEventEntity $entity) => $this->deserializeEvent($entity),
            $entities
        );
    }

    public function getAllEvents(): array
    {
        $entities = $this->repository->findAll();
        
        return array_map(
            fn(DomainEventEntity $entity) => $this->deserializeEvent($entity),
            $entities
        );
    }

    public function getEventCount(string $aggregateId): int
    {
        return count($this->getEvents($aggregateId));
    }

    private function serializeEvent(DomainEvent $event): string
    {
        $data = [
            'eventType' => $event->getEventType(),
            'aggregateId' => $event->getAggregateId(),
            'occurredOn' => $event->getOccurredOn()->format('c'), // ISO8601 format
        ];

        // Add event-specific data using reflection
        $reflection = new \ReflectionClass($event);
        foreach ($reflection->getProperties() as $property) {
            if (!$property->isPublic()) {
                $property->setAccessible(true);
            }
            $value = $property->getValue($event);
            
            // Skip already added fields
            if (in_array($property->getName(), ['orderId', 'occurredOn'])) {
                continue;
            }

            if ($value instanceof \DateTimeImmutable) {
                $data[$property->getName()] = $value->format('c'); // ISO8601 format
            } else {
                $data[$property->getName()] = $value;
            }
        }

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function deserializeEvent(DomainEventEntity $entity): DomainEvent
    {
        $data = json_decode($entity->getEventData(), true, 512, JSON_THROW_ON_ERROR);
        
        $eventType = $data['eventType'];
        $eventClass = "App\\Domain\\Event\\{$eventType}";

        if (!class_exists($eventClass)) {
            throw new \RuntimeException("Event class {$eventClass} not found");
        }

        // Reconstruct event with proper types
        $occurredOn = \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ISO8601, $data['occurredOn']) 
            ?: new \DateTimeImmutable($data['occurredOn']);

        return match ($eventType) {
            'OrderCreated' => new \App\Domain\Event\OrderCreated(
                orderId: $data['aggregateId'],
                customerId: $data['customerId'],
                items: $data['items'],
                occurredOn: $occurredOn
            ),
            'OrderPrepared' => new \App\Domain\Event\OrderPrepared(
                orderId: $data['aggregateId'],
                occurredOn: $occurredOn
            ),
            'OrderShipped' => new \App\Domain\Event\OrderShipped(
                orderId: $data['aggregateId'],
                trackingNumber: $data['trackingNumber'],
                occurredOn: $occurredOn
            ),
            'OrderDelivered' => new \App\Domain\Event\OrderDelivered(
                orderId: $data['aggregateId'],
                occurredOn: $occurredOn
            ),
            'FeedbackWindowStarted' => new \App\Domain\Event\FeedbackWindowStarted(
                orderId: $data['aggregateId'],
                windowStartDate: \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ISO8601, $data['windowStartDate']) 
                    ?: new \DateTimeImmutable($data['windowStartDate']),
                windowEndDate: \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ISO8601, $data['windowEndDate']) 
                    ?: new \DateTimeImmutable($data['windowEndDate']),
                occurredOn: $occurredOn
            ),
            'ReturnRequested' => new \App\Domain\Event\ReturnRequested(
                orderId: $data['aggregateId'],
                reason: $data['reason'],
                occurredOn: $occurredOn
            ),
            'ItemReceivedBack' => new \App\Domain\Event\ItemReceivedBack(
                orderId: $data['aggregateId'],
                warehouseId: $data['warehouseId'],
                occurredOn: $occurredOn
            ),
            'RefundIssued' => new \App\Domain\Event\RefundIssued(
                orderId: $data['aggregateId'],
                amount: $data['amount'],
                refundTransactionId: $data['refundTransactionId'],
                occurredOn: $occurredOn
            ),
            default => throw new \RuntimeException("Unknown event type: {$eventType}")
        };
    }
}

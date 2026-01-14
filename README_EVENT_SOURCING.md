# Event Sourcing & CQRS Implementation - Order Lifecycle

This is a complete reference implementation of Event Sourcing and CQRS patterns for managing an Order lifecycle in Symfony/PHP.

## Architecture Overview

### Command → Event → Projection Flow

```
Command → CommandHandler → Aggregate → Event → EventStore → Projection → ReadModel
```

## Components

### 1. Domain Events (`src/Domain/Event/`)
Immutable events representing what happened in the system:
- `OrderCreated` - Order initiated
- `OrderPrepared` - Order in preparation
- `OrderShipped` - Order dispatched
- `OrderDelivered` - Order arrived
- `FeedbackWindowStarted` - 14-day return window begins
- `ReturnRequested` - User triggers return
- `ItemReceivedBack` - Warehouse receipt
- `RefundIssued` - Closure

### 2. Commands (`src/Domain/Command/`)
Commands represent user intentions:
- `CreateOrder`
- `PrepareOrder`
- `ShipOrder`
- `DeliverOrder`
- `StartFeedbackWindow`
- `RequestReturn`
- `ReceiveItemBack`
- `IssueRefund`

### 3. OrderAggregate (`src/Domain/Aggregate/OrderAggregate.php`)
The aggregate root that:
- Handles commands and validates business rules
- Applies events to maintain internal state
- Maintains state: `status`, `isReturnable`, `deliveredAt`, `feedbackWindowEndDate`

**Key Features:**
- State reconstruction from event stream
- Snapshot support for performance optimization
- Business rule validation (e.g., return window validation)

### 4. Event Store (`src/Infrastructure/EventStore/`)
In-memory event store that:
- Appends immutable events
- Retrieves events by aggregate ID
- Maintains event sequence

### 5. Snapshot Store (`src/Infrastructure/Snapshot/`)
Snapshot mechanism that:
- Saves aggregate state every 5 events
- Optimizes aggregate reconstruction performance
- Reduces event replay overhead

### 6. OrderProjection (`src/Application/Projection/OrderProjection.php`)
Projection that:
- Listens to events
- Updates `OrderReadModel` (flat representation)
- Provides optimized read views for UI

### 7. OrderReadModel (`src/ReadModel/OrderReadModel.php`)
Read-optimized model for:
- UI list views
- Query operations
- Reporting

## Business Rules & Validation

### Return Request Validation
The `RequestReturn` command validates:
1. **Order must be delivered** - `OrderDelivered` event must have occurred
2. **14-day window must not have expired** - `FeedbackWindowStarted` event must have occurred and window must be active

```php
// Validation in OrderAggregate::handleRequestReturn()
if ($this->deliveredAt === null) {
    throw new \DomainException('Cannot request return: Order has not been delivered yet');
}

if ($this->feedbackWindowEndDate === null) {
    throw new \DomainException('Cannot request return: Feedback window has not started');
}

$now = new \DateTimeImmutable();
if ($now > $this->feedbackWindowEndDate) {
    throw new \DomainException('Cannot request return: 14-day return window has expired');
}
```

## API Endpoints

### Create Order
```bash
POST /api/orders
{
    "orderId": "order_123",
    "customerId": "customer_456",
    "items": [{"productId": "prod_1", "quantity": 2}]
}
```

### Prepare Order
```bash
POST /api/orders/{orderId}/prepare
```

### Ship Order
```bash
POST /api/orders/{orderId}/ship
{
    "trackingNumber": "TRACK123456"
}
```

### Deliver Order
```bash
POST /api/orders/{orderId}/deliver
```

### Start Feedback Window
```bash
POST /api/orders/{orderId}/start-feedback-window
```

### Request Return
```bash
POST /api/orders/{orderId}/request-return
{
    "reason": "Product defective"
}
```

### Receive Item Back
```bash
POST /api/orders/{orderId}/receive-item-back
{
    "warehouseId": "warehouse_1"
}
```

### Issue Refund
```bash
POST /api/orders/{orderId}/issue-refund
{
    "amount": 99.99,
    "refundTransactionId": "refund_txn_123"
}
```

### List Orders
```bash
GET /api/orders
```

### Get Order
```bash
GET /api/orders/{orderId}
```

## Usage Example

### Complete Order Lifecycle Flow

```php
// 1. Create Order
POST /api/orders
{
    "orderId": "order_001",
    "customerId": "customer_001",
    "items": [{"productId": "prod_1", "quantity": 1}]
}

// 2. Prepare Order
POST /api/orders/order_001/prepare

// 3. Ship Order
POST /api/orders/order_001/ship
{
    "trackingNumber": "TRACK001"
}

// 4. Deliver Order
POST /api/orders/order_001/deliver

// 5. Start Feedback Window (14-day return period)
POST /api/orders/order_001/start-feedback-window

// 6. Request Return (within 14 days)
POST /api/orders/order_001/request-return
{
    "reason": "Changed my mind"
}

// 7. Receive Item Back
POST /api/orders/order_001/receive-item-back
{
    "warehouseId": "warehouse_001"
}

// 8. Issue Refund
POST /api/orders/order_001/issue-refund
{
    "amount": 99.99,
    "refundTransactionId": "refund_001"
}
```

## Snapshot Mechanism

Snapshots are automatically created every 5 events to optimize aggregate reconstruction:

```php
// In OrderRepository::save()
$eventCount = $this->eventStore->getEventCount($aggregate->getId());
if ($eventCount % self::SNAPSHOT_INTERVAL === 0) {
    $this->snapshotStore->save($aggregate->getId(), $aggregate->createSnapshot());
}
```

When loading an aggregate:
1. Load latest snapshot (if exists)
2. Load events from snapshot version onwards
3. Replay events to reconstruct current state

## Testing the Implementation

### Test Return Validation

```bash
# Try to return before delivery (should fail)
POST /api/orders/order_001/request-return
{
    "reason": "Test"
}
# Expected: "Cannot request return: Order has not been delivered yet"

# After delivery and feedback window start, return should succeed
POST /api/orders/order_001/deliver
POST /api/orders/order_001/start-feedback-window
POST /api/orders/order_001/request-return
{
    "reason": "Test"
}
# Expected: Success
```

## Key Design Patterns

1. **Event Sourcing**: All state changes are stored as events
2. **CQRS**: Separate read (Projection/ReadModel) and write (Aggregate/Commands) models
3. **Aggregate Root**: OrderAggregate encapsulates business logic
4. **Snapshot Pattern**: Optimizes event replay performance
5. **Projection Pattern**: Maintains denormalized read models

## Benefits

- **Audit Trail**: Complete history of all changes
- **Time Travel**: Reconstruct state at any point in time
- **Scalability**: Read and write models can scale independently
- **Performance**: Snapshots reduce event replay overhead
- **Business Rules**: Validation enforced at aggregate level

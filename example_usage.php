<?php

/**
 * Example usage of Event Sourcing & CQRS implementation
 * 
 * This demonstrates the complete Order lifecycle flow
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Application\CommandHandler\OrderCommandHandler;
use App\Application\Projection\OrderProjection;
use App\Domain\Aggregate\OrderRepository;
use App\Domain\Command\CreateOrder;
use App\Domain\Command\DeliverOrder;
use App\Domain\Command\PrepareOrder;
use App\Domain\Command\RequestReturn;
use App\Domain\Command\ShipOrder;
use App\Domain\Command\StartFeedbackWindow;
use App\Infrastructure\EventStore\InMemoryEventStore;
use App\Infrastructure\Snapshot\InMemorySnapshotStore;

// Initialize infrastructure
$eventStore = new InMemoryEventStore();
$snapshotStore = new InMemorySnapshotStore();
$repository = new OrderRepository($eventStore, $snapshotStore);
$projection = new OrderProjection();
$commandHandler = new OrderCommandHandler($repository, $projection);

// Order ID
$orderId = 'order_' . uniqid();

echo "=== Event Sourcing & CQRS Order Lifecycle Demo ===\n\n";

// 1. Create Order
echo "1. Creating order: $orderId\n";
$commandHandler->handle(new CreateOrder(
    orderId: $orderId,
    customerId: 'customer_001',
    items: [['productId' => 'prod_1', 'quantity' => 2, 'price' => 99.99]]
));
echo "   Status: " . $projection->get($orderId)?->getStatus() . "\n\n";

// 2. Prepare Order
echo "2. Preparing order...\n";
$commandHandler->handle(new PrepareOrder($orderId));
echo "   Status: " . $projection->get($orderId)?->getStatus() . "\n\n";

// 3. Ship Order
echo "3. Shipping order...\n";
$commandHandler->handle(new ShipOrder($orderId, trackingNumber: 'TRACK123456'));
$readModel = $projection->get($orderId);
echo "   Status: " . $readModel->getStatus() . "\n";
echo "   Tracking: " . $readModel->getTrackingNumber() . "\n\n";

// 4. Deliver Order
echo "4. Delivering order...\n";
$commandHandler->handle(new DeliverOrder($orderId));
echo "   Status: " . $projection->get($orderId)?->getStatus() . "\n";
echo "   Delivered At: " . $projection->get($orderId)?->getDeliveredAt()?->format('Y-m-d H:i:s') . "\n\n";

// 5. Start Feedback Window
echo "5. Starting 14-day feedback window...\n";
$commandHandler->handle(new StartFeedbackWindow($orderId));
$readModel = $projection->get($orderId);
echo "   Status: " . $readModel->getStatus() . "\n";
echo "   Is Returnable: " . ($readModel->isReturnable() ? 'Yes' : 'No') . "\n";
echo "   Return Window Ends: " . $readModel->getFeedbackWindowEndDate()?->format('Y-m-d H:i:s') . "\n\n";

// 6. Try to return (should succeed)
echo "6. Requesting return...\n";
try {
    $commandHandler->handle(new RequestReturn($orderId, reason: 'Changed my mind'));
    echo "   ✓ Return requested successfully\n";
    echo "   Status: " . $projection->get($orderId)?->getStatus() . "\n";
    echo "   Return Reason: " . $projection->get($orderId)?->getReturnReason() . "\n\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// Display final state
echo "=== Final Order State ===\n";
$finalState = $projection->get($orderId);
print_r($finalState->toArray());

echo "\n=== Event Store Statistics ===\n";
echo "Total Events: " . count($eventStore->getEvents($orderId)) . "\n";
echo "Snapshot Created: " . ($snapshotStore->get($orderId) !== null ? 'Yes' : 'No') . "\n";

if ($snapshotStore->get($orderId) !== null) {
    $snapshot = $snapshotStore->get($orderId);
    echo "Snapshot Version: " . $snapshot->getVersion() . "\n";
}

echo "\n=== Demo Complete ===\n";

# Trigger-Based Event Sourcing System - Workflow Documentation

## Overview

This system extends Event Sourcing with **Trigger-Based Recalculation** - a pattern for handling time-sensitive calculations where some changes need immediate processing while others should be scheduled for future recalculation.

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    TRIGGER-BASED EVENT SOURCING                  │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐         ┌──────────────────┐         ┌──────────────┐
│   External   │────────▶│   Trigger API    │────────▶│   Trigger    │
│   System     │         │   Endpoint       │         │   Handler    │
└──────────────┘         └──────────────────┘         └──────────────┘
                                                               │
                                                               ▼
┌─────────────────────────────────────────────────────────────────┐
│                    TRIGGER PROCESSOR                            │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Input: Trigger (id, name, payload)                      │  │
│  │                                                           │  │
│  │  Logic:                                                   │  │
│  │  • Check if recalculation needed NOW or LATER            │  │
│  │  • If date > today → Schedule for future                 │  │
│  │  • If date <= today → Process immediately                │  │
│  │                                                           │  │
│  │  Output:                                                  │  │
│  │  • Events (if immediate)                                 │  │
│  │  • Next Recalculation Date (if scheduled)               │  │
│  │  • Recalculate Now flag                                   │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
         │                                    │
         │ Immediate                          │ Scheduled
         ▼                                    ▼
┌──────────────────┐              ┌──────────────────────────┐
│  Apply Events    │              │  Store Trigger in DB      │
│  to Aggregate    │              │  with recalculation_date │
└──────────────────┘              └──────────────────────────┘
         │                                    │
         │                                    │
         ▼                                    ▼
┌─────────────────────────────────────────────────────────────┐
│              EVENT STORE (Database)                          │
│  ┌──────────────────┐         ┌──────────────────┐            │
│  │  domain_events  │         │  triggers        │            │
│  │  (immutable)    │         │  (scheduled)     │            │
│  └──────────────────┘         └──────────────────┘            │
└─────────────────────────────────────────────────────────────┘
         │                                    │
         │                                    │
         │                                    │ (Cron Job)
         │                                    │
         ▼                                    ▼
┌─────────────────────────────────────────────────────────────┐
│              SCHEDULED RECALCULATION                          │
│  ┌──────────────────────────────────────────────────────┐    │
│  │  Cron: app:process-scheduled-recalculations         │    │
│  │                                                      │    │
│  │  1. Find triggers where recalculation_date <= now  │    │
│  │  2. Re-process trigger                              │    │
│  │  3. Generate events                                 │    │
│  │  4. Apply to aggregate                               │    │
│  │  5. Mark trigger as 'applied'                       │    │
│  └──────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────┐
│              EVENT PROCESSOR                                  │
│  ┌──────────────────────────────────────────────────────┐    │
│  │  Input: All events for aggregate                   │    │
│  │                                                      │    │
│  │  Process:                                           │    │
│  │  • Load snapshot (if exists)                        │    │
│  │  • Replay events from snapshot                      │    │
│  │  • Calculate current state                         │    │
│  │  • Determine next recalculation date               │    │
│  │                                                      │    │
│  │  Output:                                            │    │
│  │  • Calculated Aggregate                            │    │
│  │  • Next Recalculation Date                         │    │
│  └──────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────┐
│              PROJECTION / READ MODEL                          │
│  ┌──────────────────┐         ┌──────────────────┐          │
│  │  OrderReadModel  │         │  Activity Table   │          │
│  │  (flat view)     │         │  (received/applied│          │
│  └──────────────────┘         └──────────────────┘          │
└─────────────────────────────────────────────────────────────┘
```

---

## Complete Workflow Sequence

### Scenario 1: Immediate Processing (Date <= Today)

```
Time: 14.01.2026

┌─────────────┐
│  1. Trigger │  order_update_delivery_date
│   Received  │  payload: {delivery_date: "14.01.2026"}
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────┐
│  2. Trigger Processor               │
│  • Check date: 14.01.2026 <= today  │
│  • Decision: RECALCULATE NOW        │
│  • Generate: OrderDelivered event   │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│  3. Apply Events                   │
│  • Load OrderAggregate             │
│  • Apply OrderDelivered            │
│  • Save to Event Store             │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│  4. Update Projection              │
│  • Update OrderReadModel           │
│  • Status: DELIVERED                │
└─────────────────────────────────────┘
```

### Scenario 2: Scheduled Processing (Date > Today)

```
Time: 14.01.2026

┌─────────────┐
│  1. Trigger │  order_update_delivery_date
│   Received  │  payload: {delivery_date: "01.02.2026"}
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────┐
│  2. Trigger Processor               │
│  • Check date: 01.02.2026 > today   │
│  • Decision: SCHEDULE FOR LATER     │
│  • Next Recalc: 01.02.2026          │
│  • Events: [] (empty)               │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│  3. Store Trigger                   │
│  • Save to triggers table          │
│  • status: 'received'               │
│  • recalculation_date: 01.02.2026  │
└─────────────────────────────────────┘

... Time passes ...

Time: 01.02.2026 (Cron Job Runs)

┌─────────────────────────────────────┐
│  4. Scheduled Recalculation         │
│  • Find triggers <= 01.02.2026      │
│  • Re-process trigger               │
│  • Now: date <= today               │
│  • Generate: OrderDelivered event   │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│  5. Apply Events                    │
│  • Load OrderAggregate              │
│  • Apply OrderDelivered             │
│  • Save to Event Store              │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│  6. Mark Trigger as Applied         │
│  • status: 'applied'                │
│  • applied_at: 01.02.2026           │
└─────────────────────────────────────┘
```

---

## Data Flow Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                    COMPLETE DATA FLOW                             │
└──────────────────────────────────────────────────────────────────┘

INPUT LAYER
    │
    │ Trigger arrives
    ▼
┌─────────────────────┐
│  triggers table      │  status: 'received'
│  - trigger_id        │  recalculation_date: future_date
│  - aggregate_id       │
│  - name              │
│  - payload (JSON)    │
│  - status            │
└─────────────────────┘
    │
    │ (Cron processes scheduled triggers)
    ▼
PROCESSING LAYER
    │
    ├─▶ Trigger Processor
    │   ├─▶ Immediate? → Generate Events → Apply Now
    │   └─▶ Scheduled? → Store for later
    │
    ├─▶ Event Processor
    │   └─▶ Replay all events → Calculate state
    │
    └─▶ Aggregate
        └─▶ Apply events → Update state
    │
    ▼
STORAGE LAYER
    │
    ├─▶ domain_events table
    │   └─▶ Immutable event history
    │
    ├─▶ order_snapshots table
    │   └─▶ Aggregate state snapshots (every 5 events)
    │
    └─▶ triggers table
        └─▶ Scheduled recalculations
    │
    ▼
OUTPUT LAYER
    │
    ├─▶ OrderReadModel (Projection)
    │   └─▶ Current state for UI
    │
    └─▶ activity_con_pay table
        └─▶ Activity tracking (received/applied)
```

---

## Activity Tracking Flow

```
┌─────────────────────────────────────────────────────────────────┐
│              ACTIVITY TRACKING (activity_con_pay)               │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐
│  Activity    │  id, activity_id, amount, source, date
│  Created     │  applied: false, invalidated: false
└──────┬───────┘
       │
       ▼
┌─────────────────────┐
│  RECEIVED Queue     │  Activities waiting to be processed
│  (applied = false)  │
└──────┬──────────────┘
       │
       │ When trigger processes
       ▼
┌─────────────────────┐
│  APPLIED Queue      │  Activities that have been processed
│  (applied = true)   │  applied_at: timestamp
└─────────────────────┘
       │
       │ If new trigger invalidates
       ▼
┌─────────────────────┐
│  INVALIDATED        │  Activities superseded by newer data
│  (invalidated=true) │  (marked with X in diagram)
└─────────────────────┘
```

---

## Timeline Example (3-Month, 6-Month, 9-Month)

```
Timeline: 14.01.2026 → 01.02.2026 → 01.05.2026 → 01.10.2026

┌─────────────────────────────────────────────────────────────────┐
│  Today: 14.01.2026                                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Trigger Received:                                               │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ order_update_delivery_date                               │   │
│  │ delivery_date: 01.02.2026                                │   │
│  │                                                           │   │
│  │ → Stored in triggers table                              │   │
│  │ → recalculation_date: 01.02.2026                        │   │
│  │ → status: 'received'                                     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  Scheduled: 01.02.2026 (Cron Job)                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Cron finds trigger:                                             │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ recalculation_date <= 01.02.2026                        │   │
│  │                                                           │   │
│  │ → Re-process trigger                                     │   │
│  │ → Generate OrderDelivered event                          │   │
│  │ → Apply to aggregate                                     │   │
│  │ → Mark trigger as 'applied'                             │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  Activity Table:                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ received: [Activity1, Activity2]                        │   │
│  │ applied:  [Activity1 ✓]                                  │   │
│  │          [Activity2 ✗] (invalidated by new trigger)    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Component Interactions

```
┌──────────────┐
│   API Call   │  POST /api/triggers
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────┐
│  TriggerController                                           │
│  • Receives trigger data                                     │
│  • Creates Trigger domain object                             │
│  • Calls TriggerHandler                                      │
└──────┬───────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────┐
│  TriggerHandler                                              │
│  • Invalidates conflicting triggers                           │
│  • Calls TriggerProcessor                                    │
│  • Stores trigger in database                                │
│  • Applies events if immediate                               │
└──────┬───────────────────────────────────────────────────────┘
       │
       ├─────────────────┐
       │                 │
       ▼                 ▼
┌──────────────┐  ┌──────────────────────┐
│ Trigger      │  │  Store in DB          │
│ Processor    │  │  (scheduled)          │
│              │  │                       │
│ • Analyze    │  │  triggers table:     │
│ • Decide:    │  │  - status: received   │
│   NOW/LATER  │  │  - recalculation_date │
└──────┬───────┘  └──────────────────────┘
       │
       │ (if immediate)
       ▼
┌─────────────────────────────────────────────────────────────┐
│  OrderRepository                                             │
│  • Load aggregate from event stream                          │
│  • Apply events                                              │
│  • Save events to database                                   │
│  • Create snapshot if needed                                 │
└──────┬───────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────┐
│  OrderProjection                                             │
│  • Update read model                                         │
│  • Update activity table                                     │
└─────────────────────────────────────────────────────────────┘
```

---

## Cron Job Workflow

```
┌─────────────────────────────────────────────────────────────┐
│  Cron Schedule (runs every hour/day)                        │
└─────────────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────┐
│  ProcessScheduledRecalculationsCommand                       │
│  php bin/console app:process-scheduled-recalculations       │
└──────┬───────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────┐
│  1. Query triggers table                                     │
│     WHERE status = 'received'                                │
│     AND recalculation_date <= NOW()                          │
└──────┬───────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────┐
│  2. For each trigger:                                        │
│     • Re-process trigger                                     │
│     • Generate events                                        │
│     • Apply to aggregate                                     │
│     • Mark as 'applied'                                      │
└──────┬───────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────┐
│  3. Return count of processed triggers                       │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Schema Relationships

```
┌─────────────────────────────────────────────────────────────┐
│                    DATABASE SCHEMA                            │
└─────────────────────────────────────────────────────────────┘

domain_events
├─ id (PK)
├─ aggregate_id (FK → orders)
├─ event_type
├─ event_data (JSON)
├─ occurred_on
└─ sequence
    │
    │ Many events per aggregate
    │
order_snapshots
├─ id (PK)
├─ order_id (FK → orders, UNIQUE)
├─ status
├─ is_returnable
├─ delivered_at
├─ feedback_window_end_date
├─ version
└─ created_at
    │
    │ One snapshot per aggregate (latest)
    │
triggers
├─ id (PK)
├─ trigger_id (unique identifier)
├─ aggregate_id (FK → orders)
├─ name (trigger type)
├─ payload (JSON)
├─ status (received/applied/invalidated)
├─ recalculation_date (when to process)
├─ received_at
└─ applied_at
    │
    │ Many triggers per aggregate
    │ Can be scheduled for future
    │
activity_con_pay
├─ id (PK)
├─ activity_id
├─ amount
├─ source
├─ date
├─ applied (boolean)
├─ invalidated (boolean)
└─ applied_at
```

---

## Key Concepts

### 1. Trigger States

- **received**: Trigger received, waiting for recalculation date
- **applied**: Trigger processed, events generated and applied
- **invalidated**: Trigger superseded by newer trigger

### 2. Recalculation Logic

- **Immediate**: If trigger date <= today → Process now
- **Scheduled**: If trigger date > today → Store for later

### 3. Event Invalidation

When a new trigger arrives that conflicts with existing triggers:
- Old triggers are marked as 'invalidated'
- New trigger takes precedence
- Prevents duplicate processing

### 4. Activity Tracking

- **received**: Activities waiting to be processed
- **applied**: Activities that have been processed
- **invalidated**: Activities superseded by newer data

---

## Usage Examples

### Example 1: Schedule Future Delivery

```bash
POST /api/triggers
{
    "triggerId": "trigger_001",
    "name": "order_update_delivery_date",
    "aggregateId": "order_001",
    "payload": {
        "delivery_date": "01.02.2026"
    }
}

Response:
{
    "success": true,
    "triggerId": "trigger_001",
    "message": "Trigger received and scheduled for 01.02.2026"
}
```

### Example 2: Immediate Processing

```bash
POST /api/triggers
{
    "triggerId": "trigger_002",
    "name": "order_update_delivery_date",
    "aggregateId": "order_001",
    "payload": {
        "delivery_date": "14.01.2026"  // Today
    }
}

Response:
{
    "success": true,
    "triggerId": "trigger_002",
    "message": "Trigger received and processed immediately"
}
```

### Example 3: Manual Recalculation

```bash
POST /api/triggers/recalculate
{
    "date": "2026-02-01"
}

Response:
{
    "success": true,
    "processed": 5,
    "date": "2026-02-01 00:00:00"
}
```

---

## Benefits

1. **Time-Aware Processing**: Handles future-dated changes correctly
2. **Efficient**: Only processes when needed
3. **Auditable**: Complete history of triggers and recalculations
4. **Scalable**: Can handle millions of scheduled recalculations
5. **Flexible**: Supports immediate and scheduled processing

---

## Next Steps

1. Set up cron job to run scheduled recalculations
2. Monitor trigger processing
3. Handle edge cases (timezone, leap years, etc.)
4. Add retry logic for failed recalculations

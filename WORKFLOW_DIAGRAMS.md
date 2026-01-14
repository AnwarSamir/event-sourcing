# Event Sourcing with Trigger-Based Recalculation - Visual Diagrams

## 1. System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    EVENT SOURCING + TRIGGER SYSTEM                       │
└─────────────────────────────────────────────────────────────────────────┘

                    ┌──────────────┐
                    │   External   │
                    │   Systems    │
                    └──────┬───────┘
                           │
                           │ Triggers
                           ▼
        ┌──────────────────────────────────────┐
        │      Trigger API Endpoints            │
        │  POST /api/triggers                   │
        └──────┬───────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────────────────┐
│                        TRIGGER HANDLER                                │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  1. Check for conflicting triggers                            │   │
│  │  2. Invalidate old conflicting triggers                       │   │
│  │  3. Process trigger                                           │   │
│  └──────────────────────────────────────────────────────────────┘   │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│                    TRIGGER PROCESSOR                                 │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  Input: Trigger (name, payload, date)                        │   │
│  │                                                               │   │
│  │  Decision Logic:                                             │   │
│  │  ┌─────────────────────────────────────────────────────┐    │   │
│  │  │ IF trigger_date <= TODAY                            │    │   │
│  │  │   → Generate Events                                 │    │   │
│  │  │   → Set recalculateNow = true                        │    │   │
│  │  │                                                      │    │   │
│  │  │ IF trigger_date > TODAY                             │    │   │
│  │  │   → Set nextRecalculationDate = trigger_date       │    │   │
│  │  │   → Set recalculateNow = false                      │    │   │
│  │  │   → Events = [] (empty)                             │    │   │
│  │  └─────────────────────────────────────────────────────┘    │   │
│  │                                                               │   │
│  │  Output: TriggerProcessingResult                             │   │
│  └──────────────────────────────────────────────────────────────┘   │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       ├──────────────────────────┬──────────────────────────┐
       │                          │                          │
       ▼                          ▼                          ▼
┌──────────────┐        ┌──────────────────┐      ┌──────────────────┐
│  Immediate   │        │   Scheduled      │      │   Store Trigger  │
│  Processing  │        │   Processing     │      │   in Database    │
│              │        │                  │      │                  │
│  Apply       │        │  Store for       │      │  triggers table  │
│  Events Now  │        │  Cron Job        │      │  status: received│
└──────┬───────┘        └──────────────────┘      └──────────────────┘
       │                          │                          │
       │                          │                          │
       └──────────┬───────────────┴──────────┬───────────────┘
                  │                          │
                  ▼                          ▼
        ┌─────────────────────────────────────────────┐
        │         EVENT STORE (Database)              │
        │  ┌──────────────┐    ┌──────────────┐      │
        │  │domain_events│    │  triggers    │      │
        │  │(immutable)  │    │  (scheduled) │      │
        │  └──────────────┘    └──────────────┘      │
        └─────────────────────────────────────────────┘
                  │                          │
                  │                          │
                  ▼                          ▼
        ┌─────────────────────────────────────────────┐
        │         PROJECTION / READ MODEL              │
        │  ┌──────────────┐    ┌──────────────┐      │
        │  │OrderReadModel│    │activity_con_ │      │
        │  │              │    │pay           │      │
        │  └──────────────┘    └──────────────┘      │
        └─────────────────────────────────────────────┘
```

## 2. Complete Request Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    COMPLETE REQUEST FLOW                             │
└─────────────────────────────────────────────────────────────────────┘

STEP 1: Trigger Received
┌─────────────────────────────────────────────────────────────────┐
│ POST /api/triggers                                              │
│ {                                                               │
│   "name": "order_update_delivery_date",                         │
│   "aggregateId": "order_001",                                  │
│   "payload": {"delivery_date": "01.02.2026"}                   │
│ }                                                               │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ▼
STEP 2: Trigger Handler
┌─────────────────────────────────────────────────────────────────┐
│ 1. Find conflicting triggers                                   │
│    → Query: WHERE aggregate_id = 'order_001'                   │
│             AND name = 'order_update_delivery_date'             │
│             AND status = 'received'                            │
│                                                                 │
│ 2. Invalidate conflicts                                         │
│    → Set status = 'invalidated'                                │
│                                                                 │
│ 3. Process trigger                                              │
│    → Call TriggerProcessor                                      │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ▼
STEP 3: Trigger Processor Decision
┌─────────────────────────────────────────────────────────────────┐
│ Today: 14.01.2026                                               │
│ Trigger Date: 01.02.2026                                        │
│                                                                 │
│ Decision Tree:                                                  │
│                                                                 │
│  01.02.2026 > 14.01.2026?                                      │
│       │                                                         │
│       ├─ YES → Schedule for future                             │
│       │         • nextRecalculationDate = 01.02.2026          │
│       │         • recalculateNow = false                       │
│       │         • events = []                                  │
│       │                                                         │
│       └─ NO → Process immediately                               │
│                 • recalculateNow = true                        │
│                 • Generate events                              │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ├─────────────────┐
                           │                 │
                           ▼                 ▼
        ┌──────────────────────┐  ┌──────────────────────┐
        │   IMMEDIATE PATH     │  │   SCHEDULED PATH     │
        └──────────────────────┘  └──────────────────────┘
                           │                 │
                           │                 │
                           ▼                 ▼
        ┌──────────────────────┐  ┌──────────────────────┐
        │ Apply Events Now     │  │ Store in triggers     │
        │                      │  │ table                 │
        │ • Load Aggregate     │  │                       │
        │ • Apply Events       │  │ status: 'received'    │
        │ • Save to EventStore │  │ recalculation_date:   │
        │ • Update Projection  │  │   01.02.2026          │
        └──────────────────────┘  └──────────────────────┘
                           │                 │
                           │                 │
                           │                 │ (Wait for Cron)
                           │                 │
                           │                 ▼
                           │        ┌──────────────────────┐
                           │        │ Cron Job Runs        │
                           │        │ 01.02.2026           │
                           │        │                      │
                           │        │ php bin/console      │
                           │        │ app:process-         │
                           │        │ scheduled-           │
                           │        │ recalculations       │
                           │        └──────────┬───────────┘
                           │                   │
                           │                   ▼
                           │        ┌──────────────────────┐
                           │        │ Find Triggers        │
                           │        │ WHERE recalculation_ │
                           │        │ date <= NOW()        │
                           │        └──────────┬───────────┘
                           │                   │
                           │                   ▼
                           │        ┌──────────────────────┐
                           │        │ Re-process Trigger    │
                           │        │ • Now date <= today   │
                           │        │ • Generate events     │
                           │        │ • Apply to aggregate  │
                           │        │ • Mark as 'applied'   │
                           │        └──────────────────────┘
                           │
                           └───────────────────┘
                                   │
                                   ▼
                    ┌──────────────────────────────┐
                    │   EVENT STORE UPDATED        │
                    │   • Events persisted         │
                    │   • Snapshot created (if 5th)│
                    └──────────────────────────────┘
                                   │
                                   ▼
                    ┌──────────────────────────────┐
                    │   PROJECTION UPDATED         │
                    │   • OrderReadModel refreshed │
                    │   • Activity table updated   │
                    └──────────────────────────────┘
```

## 3. Timeline Visualization

```
┌─────────────────────────────────────────────────────────────────────┐
│                    TIMELINE EXAMPLE                                  │
└─────────────────────────────────────────────────────────────────────┘

Today: 14.01.2026

┌─────────────────────────────────────────────────────────────────────┐
│  Timeline: 14.01 → 01.02 → 01.05 → 01.10                           │
└─────────────────────────────────────────────────────────────────────┘

14.01.2026 (Today)
│
│  ┌─────────────────────────────────────────────┐
│  │ Trigger Received                            │
│  │ order_update_delivery_date                   │
│  │ delivery_date: 01.02.2026                   │
│  │                                              │
│  │ → Stored in triggers table                  │
│  │ → status: 'received'                        │
│  │ → recalculation_date: 01.02.2026            │
│  └─────────────────────────────────────────────┘
│
│  Activity Table:
│  ┌─────────────────────────────────────────────┐
│  │ received: [Activity1, Activity2, Activity3] │
│  │ applied:  []                                 │
│  └─────────────────────────────────────────────┘
│
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  01.02.2026 (Scheduled Recalculation)                              │
│  │                                                                 │
│  │  ┌─────────────────────────────────────────────┐              │
│  │  │ Cron Job Executes                          │              │
│  │  │                                              │              │
│  │  │ 1. Find triggers <= 01.02.2026              │              │
│  │  │ 2. Re-process trigger                       │              │
│  │  │ 3. Generate OrderDelivered event            │              │
│  │  │ 4. Apply to aggregate                      │              │
│  │  │ 5. Mark trigger as 'applied'                │              │
│  │  └─────────────────────────────────────────────┘              │
│  │                                                                 │
│  │  Activity Table:                                               │
│  │  ┌─────────────────────────────────────────────┐            │
│  │  │ received: [Activity2, Activity3]             │            │
│  │  │ applied:  [Activity1 ✓]                      │            │
│  │  │          [Activity2 ✗] (invalidated)         │            │
│  │  └─────────────────────────────────────────────┘            │
│  │                                                                 │
│  │  Event Store:                                                  │
│  │  ┌─────────────────────────────────────────────┐            │
│  │  │ OrderDelivered event added                   │            │
│  │  │ Aggregate state: DELIVERED                   │            │
│  │  └─────────────────────────────────────────────┘            │
│  │                                                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  01.05.2026 (Future Scheduled)                                     │
│  │                                                                 │
│  │  ┌─────────────────────────────────────────────┐              │
│  │  │ Another trigger scheduled                   │              │
│  │  │ recalculation_date: 01.05.2026              │              │
│  │  │ (Waiting for cron)                          │              │
│  │  └─────────────────────────────────────────────┘              │
│  │                                                                 │
└─────────────────────────────────────────────────────────────────────┘
```

## 4. State Transitions

```
┌─────────────────────────────────────────────────────────────────────┐
│                    TRIGGER STATE MACHINE                             │
└─────────────────────────────────────────────────────────────────────┘

                    ┌─────────────┐
                    │   received  │  ← New trigger arrives
                    └──────┬──────┘
                           │
                           │ (if date <= today)
                           ▼
                    ┌─────────────┐
                    │   applied   │  ← Events generated & applied
                    └─────────────┘
                           │
                           │ (if conflicting trigger arrives)
                           ▼
                    ┌─────────────┐
                    │ invalidated │  ← Superseded by newer trigger
                    └─────────────┘


┌─────────────────────────────────────────────────────────────────────┐
│                    ACTIVITY STATE MACHINE                            │
└─────────────────────────────────────────────────────────────────────┘

                    ┌─────────────┐
                    │  received   │  ← Activity created
                    │ applied:false│
                    └──────┬──────┘
                           │
                           │ (when trigger processes)
                           ▼
                    ┌─────────────┐
                    │   applied   │  ← Activity processed
                    │ applied:true│
                    └──────┬──────┘
                           │
                           │ (if new trigger invalidates)
                           ▼
                    ┌─────────────┐
                    │ invalidated │  ← Superseded (marked with X)
                    │invalidated: │
                    │    true     │
                    └─────────────┘
```

## 5. Data Flow Between Components

```
┌─────────────────────────────────────────────────────────────────────┐
│                    DATA FLOW DIAGRAM                                 │
└─────────────────────────────────────────────────────────────────────┘

External System
      │
      │ POST /api/triggers
      ▼
┌─────────────────────────────────────────────────────────────────┐
│  TriggerController                                              │
│  • Validates input                                              │
│  • Creates Trigger domain object                                │
└──────┬──────────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────────┐
│  TriggerHandler                                                 │
│  • Invalidates conflicts                                        │
│  • Calls TriggerProcessor                                       │
└──────┬──────────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────────┐
│  TriggerProcessor                                               │
│  • Analyzes trigger date                                        │
│  • Decides: NOW or LATER                                        │
│  • Returns TriggerProcessingResult                               │
└──────┬──────────────────────────────────────────────────────────┘
       │
       ├──────────────────────┬──────────────────────┐
       │                      │                      │
       ▼                      ▼                      ▼
┌──────────────┐    ┌──────────────────┐  ┌──────────────────┐
│ Immediate:   │    │ Scheduled:       │  │ Store Trigger:   │
│              │    │                  │  │                  │
│ Apply Events │    │ Store in DB      │  │ triggers table   │
│ Now          │    │ for Cron         │  │                  │
└──────┬───────┘    └──────────────────┘  └──────────────────┘
       │                      │                      │
       │                      │                      │
       └──────────┬───────────┴──────────┬───────────┘
                  │                      │
                  ▼                      ▼
        ┌─────────────────────────────────────────┐
        │  OrderRepository                         │
        │  • Load aggregate from events             │
        │  • Apply new events                      │
        │  • Save to EventStore                    │
        │  • Create snapshot (every 5 events)      │
        └──────┬────────────────────────────────────┘
               │
               ▼
        ┌─────────────────────────────────────────┐
        │  OrderProjection                        │
        │  • Update OrderReadModel                │
        │  • Update activity_con_pay table        │
        │  • Mark activities as applied           │
        └─────────────────────────────────────────┘
```

## 6. Cron Job Execution Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    CRON JOB EXECUTION                                │
└─────────────────────────────────────────────────────────────────────┘

Cron Schedule (runs every hour)
      │
      │ php bin/console app:process-scheduled-recalculations
      ▼
┌─────────────────────────────────────────────────────────────────┐
│  ProcessScheduledRecalculationsCommand                           │
│                                                                 │
│  1. Get current date/time                                       │
│     now = new DateTimeImmutable()                               │
└──────┬──────────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────────┐
│  Query triggers table                                            │
│                                                                 │
│  SELECT * FROM triggers                                          │
│  WHERE status = 'received'                                      │
│    AND recalculation_date <= :now                               │
│  ORDER BY recalculation_date ASC                                 │
└──────┬──────────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────────┐
│  For each trigger found:                                         │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ 1. Re-create Trigger domain object                       │  │
│  │ 2. Re-process via TriggerProcessor                       │  │
│  │ 3. Check if events generated                             │  │
│  │ 4. If yes:                                               │  │
│  │    • Apply events to aggregate                          │  │
│  │    • Save to EventStore                                  │  │
│  │    • Mark trigger as 'applied'                          │  │
│  │ 5. Update projection                                     │  │
│  └──────────────────────────────────────────────────────────┘  │
└──────┬──────────────────────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────────┐
│  Return count of processed triggers                              │
│  Log results                                                     │
└─────────────────────────────────────────────────────────────────┘
```

## 7. Integration with Existing Order Lifecycle

```
┌─────────────────────────────────────────────────────────────────────┐
│        TRIGGER SYSTEM + ORDER LIFECYCLE INTEGRATION                  │
└─────────────────────────────────────────────────────────────────────┘

Traditional Order Flow:
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Created  │───▶│ Prepared │───▶│ Shipped  │───▶│ Delivered│
└──────────┘    └──────────┘    └──────────┘    └──────────┘
                                                         │
                                                         ▼
                                            ┌──────────────────────┐
                                            │ Feedback Window      │
                                            │ Started               │
                                            └──────────────────────┘

With Trigger System:
┌──────────┐    ┌──────────┐    ┌──────────┐
│ Created  │───▶│ Prepared │───▶│ Shipped  │
└──────────┘    └──────────┘    └──────────┘
                                                         │
                                                         │ Trigger:
                                                         │ delivery_date
                                                         │ = 01.02.2026
                                                         ▼
                                            ┌──────────────────────┐
                                            │ Trigger Stored       │
                                            │ Scheduled: 01.02.2026│
                                            └──────────────────────┘
                                                         │
                                                         │ (Cron on 01.02)
                                                         ▼
                                            ┌──────────────────────┐
                                            │ OrderDelivered       │
                                            │ Event Generated      │
                                            │ & Applied            │
                                            └──────────────────────┘
                                                         │
                                                         ▼
                                            ┌──────────────────────┐
                                            │ Feedback Window      │
                                            │ Started               │
                                            └──────────────────────┘
```

## 8. Database Tables Relationships

```
┌─────────────────────────────────────────────────────────────────────┐
│                    DATABASE RELATIONSHIPS                            │
└─────────────────────────────────────────────────────────────────────┘

orders (conceptual)
    │
    ├───┐
    │   │
    │   ├──▶ domain_events (1:N)
    │   │    • All events for this order
    │   │    • Immutable history
    │   │
    │   ├──▶ order_snapshots (1:1)
    │   │    • Latest snapshot
    │   │    • Every 5 events
    │   │
    │   ├──▶ triggers (1:N)
    │   │    • Scheduled recalculations
    │   │    • status: received/applied/invalidated
    │   │
    │   └──▶ activity_con_pay (1:N)
    │        • Activity tracking
    │        • received/applied/invalidated states
    │
    └───┘

Example Data Flow:
order_001
    │
    ├── domain_events (6 events)
    │   ├─ OrderCreated
    │   ├─ OrderPrepared
    │   ├─ OrderShipped
    │   ├─ OrderDelivered (from trigger)
    │   ├─ FeedbackWindowStarted
    │   └─ ReturnRequested
    │
    ├── order_snapshots (1 snapshot at version 5)
    │   └─ Snapshot at OrderShipped
    │
    ├── triggers (2 triggers)
    │   ├─ trigger_001: applied (delivery_date: 01.02.2026)
    │   └─ trigger_002: received (payment_schedule: 01.05.2026)
    │
    └── activity_con_pay (3 activities)
        ├─ Activity1: applied ✓
        ├─ Activity2: invalidated ✗
        └─ Activity3: received
```

## 9. Complete System Flow (End-to-End)

```
┌─────────────────────────────────────────────────────────────────────┐
│                    END-TO-END SYSTEM FLOW                            │
└─────────────────────────────────────────────────────────────────────┘

[External System] ──┐
                    │ Trigger: order_update_delivery_date
                    │ delivery_date: 01.02.2026
                    ▼
        ┌───────────────────────────┐
        │  POST /api/triggers      │
        └───────────┬───────────────┘
                    │
                    ▼
        ┌───────────────────────────┐
        │  TriggerController        │
        │  • Validate input         │
        │  • Create Trigger object  │
        └───────────┬───────────────┘
                    │
                    ▼
        ┌───────────────────────────┐
        │  TriggerHandler           │
        │  • Check conflicts        │
        │  • Invalidate old         │
        └───────────┬───────────────┘
                    │
                    ▼
        ┌───────────────────────────┐
        │  TriggerProcessor        │
        │  • Date: 01.02.2026      │
        │  • Today: 14.01.2026     │
        │  • Decision: SCHEDULE    │
        └───────────┬───────────────┘
                    │
                    ▼
        ┌───────────────────────────┐
        │  Store in Database        │
        │  triggers table:          │
        │  • status: 'received'     │
        │  • recalculation_date:    │
        │    01.02.2026             │
        └───────────────────────────┘
                    │
                    │ (Time passes...)
                    │
                    ▼
        ┌───────────────────────────┐
        │  Cron Job (01.02.2026)    │
        │  app:process-scheduled-   │
        │  recalculations           │
        └───────────┬───────────────┘
                    │
                    ▼
        ┌───────────────────────────┐
        │  Find Triggers            │
        │  WHERE recalculation_date │
        │  <= 01.02.2026            │
        └───────────┬───────────────┘
                    │
                    ▼
        ┌───────────────────────────┐
        │  Re-process Trigger       │
        │  • Now date <= today      │
        │  • Generate events        │
        └───────────┬───────────────┘
                    │
                    ▼
        ┌───────────────────────────┐
        │  OrderRepository         │
        │  • Load aggregate        │
        │  • Apply events          │
        │  • Save to EventStore    │
        └───────────┬───────────────┘
                    │
                    ▼
        ┌───────────────────────────┐
        │  OrderProjection          │
        │  • Update ReadModel       │
        │  • Update activities      │
        └───────────┬───────────────┘
                    │
                    ▼
        ┌───────────────────────────┐
        │  Mark Trigger Applied     │
        │  • status: 'applied'      │
        │  • applied_at: 01.02.2026 │
        └───────────────────────────┘
```

## 10. Benefits of This Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                    KEY BENEFITS                                     │
└─────────────────────────────────────────────────────────────────────┘

1. TIME-AWARE PROCESSING
   ┌─────────────────────────────────────────────────────────────┐
   │ • Handles future-dated changes correctly                    │
   │ • No premature processing                                    │
   │ • Accurate timing for scheduled events                      │
   └─────────────────────────────────────────────────────────────┘

2. EFFICIENCY
   ┌─────────────────────────────────────────────────────────────┐
   │ • Only processes when needed                                │
   │ • Batch processing via cron                                 │
   │ • Reduces unnecessary calculations                          │
   └─────────────────────────────────────────────────────────────┘

3. AUDITABILITY
   ┌─────────────────────────────────────────────────────────────┐
   │ • Complete trigger history                                  │
   │ • Track when triggers were received/applied                  │
   │ • See what was invalidated                                  │
   └─────────────────────────────────────────────────────────────┘

4. SCALABILITY
   ┌─────────────────────────────────────────────────────────────┐
   │ • Can handle millions of scheduled triggers                 │
   │ • Database indexes for fast queries                         │
   │ • Efficient batch processing                                │
   └─────────────────────────────────────────────────────────────┘

5. FLEXIBILITY
   ┌─────────────────────────────────────────────────────────────┐
   │ • Supports immediate and scheduled processing               │
   │ • Easy to add new trigger types                             │
   │ • Configurable recalculation logic                          │
   └─────────────────────────────────────────────────────────────┘
```

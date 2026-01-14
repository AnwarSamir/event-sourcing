# Trigger System Integration Summary

## How the Trigger System Affects the Workflow

The trigger-based recalculation system extends the existing Event Sourcing architecture to handle **time-sensitive calculations** and **future-dated events**. Here's how it integrates:

---

## Before Trigger System

```
Traditional Event Sourcing Flow:
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Command  │───▶│ Aggregate│───▶│  Event   │───▶│ Projection│
│          │    │          │    │  Store   │    │          │
└──────────┘    └──────────┘    └──────────┘    └──────────┘

Limitation: All events must be processed immediately.
           Cannot handle "schedule this for future" scenarios.
```

---

## After Trigger System

```
Extended Event Sourcing Flow:
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Command  │───▶│ Aggregate│───▶│  Event   │───▶│ Projection│
│          │    │          │    │  Store   │    │          │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
     │                                                      │
     │                                                      │
     ▼                                                      ▼
┌──────────┐                                        ┌──────────┐
│ Trigger  │───────────────────────────────────────▶│ Activity │
│ System   │                                        │ Tracking │
└──────────┘                                        └──────────┘
     │
     ├─▶ Immediate Processing (date <= today)
     │   → Events generated & applied now
     │
     └─▶ Scheduled Processing (date > today)
         → Stored in triggers table
         → Processed by cron job later
```

---

## Key Changes to Workflow

### 1. **New Input Channel: Triggers**

**Before:**
- Only commands could create events
- Events were always immediate

**After:**
- Triggers can schedule future events
- Triggers can be processed immediately OR scheduled
- External systems can send triggers for future processing

### 2. **Time-Aware Processing**

**Before:**
```
Command: DeliverOrder
→ Event: OrderDelivered (immediate)
```

**After:**
```
Trigger: order_update_delivery_date
  delivery_date: 01.02.2026
  
  IF date <= today:
    → Event: OrderDelivered (immediate)
  ELSE:
    → Store trigger (scheduled)
    → Process on 01.02.2026 via cron
```

### 3. **Activity Tracking**

**New Feature:**
- Activities are tracked in `activity_con_pay` table
- States: `received` → `applied` → `invalidated`
- Shows which activities were processed and which were superseded

### 4. **Event Invalidation**

**New Feature:**
- Conflicting triggers invalidate older triggers
- Prevents duplicate processing
- Maintains data consistency

---

## Complete Workflow Comparison

### Scenario: Order Delivery Scheduled for Future

#### Without Trigger System:
```
❌ Cannot schedule future delivery
❌ Must process immediately
❌ No way to handle "deliver on 01.02.2026"
```

#### With Trigger System:
```
✅ Receive trigger: delivery_date = 01.02.2026
✅ Store trigger in database
✅ Cron job processes on 01.02.2026
✅ Events generated and applied automatically
✅ Order state updated correctly
```

---

## Integration Points

### 1. **Command Flow (Unchanged)**
```
POST /api/orders/{id}/deliver
  → OrderCommandHandler
  → OrderAggregate
  → OrderDelivered event
  → Event Store
  → Projection
```
**Status:** ✅ Works as before

### 2. **Trigger Flow (New)**
```
POST /api/triggers
  → TriggerHandler
  → TriggerProcessor
  → Decision: NOW or LATER
  ├─ NOW → Apply events immediately
  └─ LATER → Store trigger for cron
```
**Status:** ✅ New capability

### 3. **Cron Job Flow (New)**
```
php bin/console app:process-scheduled-recalculations
  → Find triggers <= today
  → Re-process triggers
  → Generate events
  → Apply to aggregates
  → Update projections
```
**Status:** ✅ New capability

---

## Data Flow Changes

### Event Store
**Before:** Only domain events
**After:** Domain events + Triggers table

### Projections
**Before:** OrderReadModel only
**After:** OrderReadModel + Activity tracking

### Database Schema
**New Tables:**
- `triggers` - Scheduled recalculations
- `activity_con_pay` - Activity tracking

---

## Use Cases Enabled

### 1. **Scheduled Delivery**
```
External system sends trigger:
  delivery_date: 01.02.2026
  
System stores trigger, processes on 01.02.2026
```

### 2. **Payment Scheduling**
```
Trigger: payment_schedule
  payment_date: 01.05.2026
  
System schedules payment processing
```

### 3. **Batch Processing**
```
Cron job runs daily:
  → Processes all triggers due today
  → Generates events in batch
  → Updates all affected aggregates
```

### 4. **Activity Reconciliation**
```
Track which activities were:
  - Received (waiting)
  - Applied (processed)
  - Invalidated (superseded)
```

---

## Benefits

### ✅ **Time-Aware**
- Handles future-dated events correctly
- No premature processing

### ✅ **Efficient**
- Only processes when needed
- Batch processing via cron

### ✅ **Auditable**
- Complete trigger history
- Track when triggers were received/applied

### ✅ **Scalable**
- Can handle millions of scheduled triggers
- Database indexes for fast queries

### ✅ **Flexible**
- Supports immediate and scheduled processing
- Easy to add new trigger types

---

## Migration Path

### Existing Commands
**No changes required** - Commands work exactly as before

### New Triggers
**Add new capability** - Triggers complement commands, don't replace them

### Backward Compatibility
**100% compatible** - All existing functionality preserved

---

## Example: Complete Order Lifecycle with Triggers

```
1. Create Order (Command)
   → OrderCreated event

2. Prepare Order (Command)
   → OrderPrepared event

3. Ship Order (Command)
   → OrderShipped event

4. Schedule Delivery (Trigger) ← NEW
   → Trigger stored: delivery_date = 01.02.2026

5. Cron Job (01.02.2026) ← NEW
   → Processes trigger
   → OrderDelivered event generated
   → Order state updated

6. Start Feedback Window (Command)
   → FeedbackWindowStarted event

7. Request Return (Command)
   → ReturnRequested event
```

---

## API Endpoints

### Existing (Unchanged)
- `POST /api/orders` - Create order
- `POST /api/orders/{id}/prepare` - Prepare order
- `POST /api/orders/{id}/ship` - Ship order
- `POST /api/orders/{id}/deliver` - Deliver order
- `GET /api/orders` - Get all orders
- `GET /api/orders/{id}` - Get order by ID

### New
- `POST /api/triggers` - Create trigger
- `POST /api/triggers/recalculate` - Process scheduled triggers

---

## Summary

The trigger system **extends** the existing Event Sourcing architecture without breaking any existing functionality. It adds:

1. **Time-aware processing** for future-dated events
2. **Scheduled recalculation** via cron jobs
3. **Activity tracking** for audit and reconciliation
4. **Event invalidation** to prevent conflicts

All existing commands and workflows continue to work exactly as before, while new trigger-based workflows provide additional capabilities for handling time-sensitive scenarios.

# Database-Backed Event Store Setup

## Overview

The Event Sourcing implementation now uses a **database** instead of in-memory storage. All events and snapshots are persisted to SQLite (or your configured database).

## Database Configuration

The system uses SQLite by default (configured in `.env`):

```env
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

### For MySQL:
```env
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=8.0.32&charset=utf8mb4"
```

### For PostgreSQL:
```env
DATABASE_URL="postgresql://db_user:db_password@127.0.0.1:5432/db_name?serverVersion=13&charset=utf8"
```

## Database Schema

### `domain_events` Table
Stores all domain events:
- `id` - Primary key
- `aggregate_id` - Order ID (indexed)
- `event_type` - Type of event (indexed)
- `event_data` - JSON serialized event data
- `occurred_on` - When the event occurred (indexed)
- `sequence` - Event sequence number

### `order_snapshots` Table
Stores aggregate snapshots (every 5 events):
- `id` - Primary key
- `order_id` - Order ID (unique, indexed)
- `status` - Current order status
- `is_returnable` - Whether order can be returned
- `delivered_at` - Delivery timestamp
- `feedback_window_end_date` - Return window end date
- `version` - Aggregate version at snapshot
- `created_at` - Snapshot creation time

## Migration

The database schema was created via Doctrine migrations:

```bash
php bin/console doctrine:migrations:migrate
```

## Verification

Check that events are being stored:

```bash
# Count events
sqlite3 var/data.db "SELECT COUNT(*) FROM domain_events;"

# View recent events
sqlite3 var/data.db "SELECT event_type, aggregate_id, occurred_on FROM domain_events ORDER BY sequence DESC LIMIT 10;"

# View snapshots
sqlite3 var/data.db "SELECT order_id, status, version FROM order_snapshots;"
```

## Benefits of Database Storage

1. **Persistence**: Events survive server restarts
2. **Audit Trail**: Complete history of all changes
3. **Scalability**: Can handle large volumes of events
4. **Queryability**: Can query events by type, date, aggregate, etc.
5. **Backup**: Database can be backed up and restored
6. **Production Ready**: All data is persisted, no in-memory storage

## Performance Considerations

- **Indexes**: Events are indexed by `aggregate_id`, `event_type`, and `occurred_on` for fast queries
- **Snapshots**: Snapshots are created every 5 events to optimize aggregate reconstruction
- **Batch Loading**: Events are loaded in order for efficient replay

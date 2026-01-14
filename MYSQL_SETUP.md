# MySQL Setup Guide

## Database Configuration

The Event Sourcing system is now configured to use **MySQL** instead of SQLite.

## Current Configuration

```env
DATABASE_URL="mysql://root:root@127.0.0.1:3306/event_sourcing?serverVersion=8.0&charset=utf8mb4"
```

## Setup Steps

### 1. Update Database Credentials

Edit `.env` file and update the DATABASE_URL with your MySQL credentials:

```env
DATABASE_URL="mysql://USERNAME:PASSWORD@127.0.0.1:3306/event_sourcing?serverVersion=8.0&charset=utf8mb4"
```

Replace:
- `USERNAME` - Your MySQL username (default: `root`)
- `PASSWORD` - Your MySQL password
- `event_sourcing` - Database name (create it first)

### 2. Create MySQL Database

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE event_sourcing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Exit MySQL
EXIT;
```

Or using command line:
```bash
mysql -u root -p -e "CREATE DATABASE event_sourcing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Run Migrations

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

This will create:
- `domain_events` table - Stores all events
- `order_snapshots` table - Stores aggregate snapshots

### 4. Verify Connection

```bash
php bin/console dbal:run-sql "SELECT COUNT(*) FROM domain_events"
```

## Database Schema

### domain_events Table
```sql
CREATE TABLE domain_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aggregate_id VARCHAR(255) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_data TEXT NOT NULL,
    occurred_on DATETIME NOT NULL,
    sequence INT NOT NULL,
    INDEX idx_aggregate_id (aggregate_id),
    INDEX idx_event_type (event_type),
    INDEX idx_occurred_on (occurred_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### order_snapshots Table
```sql
CREATE TABLE order_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(255) NOT NULL UNIQUE,
    status VARCHAR(50) NOT NULL,
    is_returnable BOOLEAN NOT NULL,
    delivered_at DATETIME DEFAULT NULL,
    feedback_window_end_date DATETIME DEFAULT NULL,
    version INT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Benefits of MySQL

1. **Better Performance**: Optimized for concurrent reads/writes
2. **Scalability**: Can handle large volumes of events
3. **ACID Compliance**: Full transaction support
4. **Replication**: Can replicate for high availability
5. **Backup Tools**: Rich ecosystem of backup solutions

## Troubleshooting

### Connection Refused
- Check MySQL is running: `brew services list` (macOS) or `systemctl status mysql` (Linux)
- Verify credentials in `.env`
- Check MySQL port (default: 3306)

### Access Denied
- Verify username and password
- Check MySQL user permissions:
  ```sql
  GRANT ALL PRIVILEGES ON event_sourcing.* TO 'your_user'@'localhost';
  FLUSH PRIVILEGES;
  ```

### Database Doesn't Exist
- Create the database first (see step 2 above)

## Switching Back to SQLite

If you need to switch back to SQLite:

1. Update `.env`:
   ```env
   DATABASE_URL="sqlite:///var/data.db"
   ```

2. Update `config/packages/doctrine.yaml`:
   ```yaml
   doctrine:
       dbal:
           url: '%env(resolve:DATABASE_URL)%'
           # Remove server_version for SQLite
   ```

3. Run migrations again

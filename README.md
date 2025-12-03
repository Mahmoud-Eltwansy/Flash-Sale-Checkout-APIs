# Flash Sale Checkout APIs

A high-concurrency flash sale checkout system built with Laravel 12.

It's a small API that sells a limited-stock product during a flash sale. It must handle
high concurrency without overselling, support short-lived holds, checkout, and an idempotent payment webhook.
## Features

- ✅ Thread-safe hold creation with pessimistic locking
- ✅ Automatic hold expiry with stock release
- ✅ Idempotent payment webhooks
- ✅ Out-of-order webhook handling
- ✅ Comprehensive test suite
- ✅ Structured logging for monitoring

## Requirements
- PHP 8.2+
- MySQL 8.0+ (InnoDB)
- Composer

## Installation
```bash
# Clone repository
git clone <repo-url>
cd flash-sale-api

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Update .env with database credentials
DB_DATABASE=flash_sale
DB_USERNAME=root
DB_PASSWORD=your_password

# Run migrations and seed
php artisan migrate --seed

# Start server
php artisan serve
```

## Running the Application
```bash
# Terminal 1: Start web server
php artisan serve

# Terminal 2: Start scheduler (for hold expiry)
php artisan schedule:work
```

## API Endpoints

### 1. Get Product
```bash
GET /api/products/{id}

Response:
{
    "success": true,
    "message": "Success",
    "data": {
        "id": 1,
        "name": "Iphone 17",
        "available_stock": 99,
        "price": "1100.00"
    }
}
```

### 2. Create Hold
```bash
POST /api/holds
Content-Type: application/json

{
  "product_id": 1,
  "qty": 2
}

Response (201):
{
    "success": true,
    "message": "Hold Created successfully",
    "data": {
        "quantity": 1,
        "expires_at": "2025-12-03T11:08:59.000000Z",
        "status": "active",
        "product_id": 1,
        "updated_at": "2025-12-03T11:06:59.000000Z",
        "created_at": "2025-12-03T11:06:59.000000Z",
        "id": 123
    }
}
```

### 3. Create Order
```bash
POST /api/orders
Content-Type: application/json

{
  "hold_id": 123
}

Response (201):
{
    "success": true,
    "message": "Order Created successfully",
    "data": {
        "product_id": 1,
        "hold_id": 123,
        "quantity": 1,
        "total_price": 1100,
        "updated_at": "2025-12-03T11:08:53.000000Z",
        "created_at": "2025-12-03T11:08:53.000000Z",
        "id": 30
    }
}
```

### 4. Payment Webhook
```bash
POST /api/payments/webhook
Content-Type: application/json

{
  "order_id": 30,
  "status": "success",
  "idempotency_key": "unique-key-789"
}

Response (200):
{
    "success": true,
    "message": "Success",
    "data": {
        "message": "Payment confirmed",
        "order_id": 30,
        "status": "paid"
    }
}
```

## Running Tests
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=prevents_overselling_under_concurrent_requests

```

## Architecture Decisions

### Stock Management
- **Two-column approach**: `total_stock` and `reserved_stock`
- **Available stock**: Calculated as `total_stock - reserved_stock`
- **Rationale**: Separates inventory truth from temporary holds

### Concurrency Control
- **Pessimistic locking**: `SELECT ... FOR UPDATE` on product rows
- **Transaction isolation**: All stock changes in database transactions
- **Deadlock handling**: Retry logic with 3 max attempts

### Caching Strategy
- **Cache-aside pattern**: Check cache → miss → query DB → store in cache
- **TTL**: 30 seconds (balance freshness vs performance)
- **Invalidation**: On every stock change (hold, expiry, cancellation)

### Webhook Idempotency
- **Primary key**: `idempotency_key` in `payment_webhook` table
- **Duplicate detection**: Check table before processing
- **Out-of-order safety**: Retry with exponential backoff if order not found yet

### Hold Expiry
- **Scheduled command**: Runs every minute via Laravel scheduler
- **Chunking**: Processes 100 holds per batch (prevents long transactions)
- **Locking**: Each hold processed in separate transaction with product lock

## Invariants Enforced

1. `reserved_stock <= total_stock` (database constraint)
2. `reserved_stock >= 0` (unsigned column)
3. Each hold used exactly once (unique constraint on `orders.hold_id`)
4. Each webhook processed exactly once (primary key on `idempotency_key`)

## Monitoring & Logs

**Log location**: `storage/logs/laravel.log`

**Key metrics logged**:
- Hold Expired, Hold expiry job completed, 
- Webhook duplicate count
- Hold expiry batch size and duration
- Successful payment,Failed Payment


### This is project is made by Mahmoud Eltwansy

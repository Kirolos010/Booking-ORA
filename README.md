# Bookingora

High-throughput booking and transaction API built with Laravel. Handles resource reservations with ACID database transactions to prevent double-booking, validates all incoming payloads, and publishes checkout events to a message broker for downstream processing (e.g. receipt generation).

## Task Overview

| Requirement | Implementation |
|---|---|
| Transactional booking endpoint (`POST /api/bookings`) | `BookingService::create()` wrapped in `DB::transaction()` with pessimistic row locks |
| Prevent dual-booking / race conditions | `lockForUpdate()` on `resources` and overlapping `bookings` rows inside the same transaction |
| Schema validation (Zod/Joi equivalent) | Laravel **Form Requests** (`StoreBookingRequest`, `RegisterRequest`, `LoginRequest`) |
| Publish event on checkout | `BookingCheckedOut` event → `PublishCheckoutToBroker` listener |

## Tech Stack

- **PHP** 8.3+
- **Laravel** 13
- **Laravel Sanctum** — API token authentication
- **SQLite** (default) or MySQL/MariaDB
- **Database queues** — ready for async broker publishing

## Project Structure

```
app/
├── Enums/
│   ├── BookingStatus.php      # pending, confirmed, cancelled, checked_out
│   └── ResourceTypes.php      # room, seat, flight
├── Events/
│   └── BookingCheckedOut.php  # Fired after successful checkout
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   └── BookingController.php
│   └── Requests/              # Validation wrappers (Form Requests)
│       ├── StoreBookingRequest.php
│       ├── RegisterRequest.php
│       └── LoginRequest.php
├── Listeners/
│   └── PublishCheckoutToBroker.php
├── Models/
│   ├── Booking.php
│   ├── Resource.php
│   └── User.php
└── Services/
    ├── AuthService.php
    └── BookingService.php     # Core transaction logic

database/
├── migrations/
│   ├── *_create_resources_table.php
│   └── *_create_bookings_table.php
└── seeders/
    ├── DatabaseSeeder.php
    └── ResourceSeeder.php     # Sample rooms, seats, flights

routes/
└── api.php                    # All API routes
```

## Database Schema

### `resources`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Primary key |
| `name` | string | e.g. "Room A101" |
| `type` | string(50) | `room`, `seat`, or `flight` |
| `capacity` | unsigned int | Default `1` |
| `is_active` | boolean | Default `true` |
| `meta` | json | Optional metadata (floor, route, etc.) |

### `bookings`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | Primary key |
| `booking_ref` | string(20) | Unique ref, auto-generated (`BK-XXXXXXXX`) |
| `user_id` | foreignId | References `users` |
| `resource_id` | foreignId | References `resources` |
| `resource_type` | string(50) | Copied from resource at booking time |
| `starts_at` | datetime | Booking start |
| `ends_at` | datetime | Booking end |
| `status` | string(50) | `pending`, `confirmed`, `cancelled`, `checked_out` |
| `amount` | unsigned bigint | Price in smallest currency unit |
| `currency` | string(3) | Default `EGP` |
| `payment_method` | string | Optional |
| `metadata` | json | Optional extra data |
| `confirmed_at` | timestamp | Nullable |
| `checked_out_at` | timestamp | Set on checkout |
| `deleted_at` | timestamp | Soft deletes |

**Indexes:** composite index on `(resource_id, resource_type, starts_at, ends_at)` for fast overlap checks.

## How Concurrency Is Handled

When creating a booking, `BookingService` runs inside a single database transaction:

1. **Lock the resource row** — `Resource::lockForUpdate()->findOrFail()`
2. **Check for overlapping bookings** — query existing `pending` / `confirmed` bookings on the same resource with `lockForUpdate()`
3. **Reject or insert** — throw if overlap exists; otherwise create the booking as `confirmed`

This ensures two concurrent requests for the same time slot cannot both succeed.

## API Routes

All routes are prefixed with `/api`. Protected routes require a Sanctum bearer token.

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/register` | No | Create account + receive token |
| `POST` | `/login` | No | Login + receive token |
| `POST` | `/logout` | Yes | Revoke current token |
| `GET` | `/me` | Yes | Current authenticated user |
| `POST` | `/bookings` | Yes | Create a booking (transactional) |
| `POST` | `/bookings/{booking_ref}/checkout` | Yes | Complete checkout + publish event |

## Setup

### Prerequisites

- PHP **8.3+** with extensions: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`
- [Composer](https://getcomposer.org/)
- Node.js **18+** and npm (for frontend assets; optional for API-only usage)
- MySQL/MariaDB **or** SQLite

### 1. Clone the repository

```bash
git clone https://github.com/<your-username>/bookingora.git
cd bookingora
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Environment configuration

```bash
cp .env.example .env
php artisan key:generate
```

**SQLite (default — quickest for local dev):**

```bash
# .env
DB_CONNECTION=sqlite
# DB_DATABASE is not needed; uses database/database.sqlite
touch database/database.sqlite   # Linux/macOS
# On Windows (PowerShell):
New-Item -Path database/database.sqlite -ItemType File -Force
```

**MySQL (Laragon / production):**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bookingora
DB_USERNAME=root
DB_PASSWORD=
```

Create the database first:

```sql
CREATE DATABASE bookingora;
```

### 4. Run migrations and seeders

```bash
php artisan migrate --seed
```

This creates tables and seeds:

- **Test user:** `test@example.com` / password from factory (`password` by default)
- **8 sample resources:** 3 rooms, 3 seats, 2 flights

### 5. Start the development server

```bash
php artisan serve
```

API base URL: `http://127.0.0.1:8000/api`

**One-command setup** (install, env, migrate, npm build):

```bash
composer setup
```

**Full dev stack** (server + queue worker + logs + Vite):

```bash
composer dev
```

### Laragon (Windows)

1. Place the project in `C:\laragon\www\bookingora`
2. Configure `.env` for MySQL (`DB_DATABASE=bookingora`)
3. Run `php artisan migrate --seed` from the project directory
4. Access via Laragon virtual host or `php artisan serve`

## API Usage Examples

### Register

```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

Save the `token` from the response for authenticated requests.

### Create a booking

```bash
curl -X POST http://127.0.0.1:8000/api/bookings \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "resource_id": 1,
    "starts_at": "2026-07-01 10:00:00",
    "ends_at": "2026-07-01 12:00:00",
    "amount": 50000,
    "currency": "EGP",
    "payment_method": "card"
  }'
```

**Validation rules (`StoreBookingRequest`):**

| Field | Rules |
|---|---|
| `resource_id` | required, must exist in `resources` |
| `starts_at` | required, date, must be in the future |
| `ends_at` | required, date, must be after `starts_at` |
| `amount` | required, integer, min 1 |
| `currency` | optional, 3-character string |
| `payment_method` | optional string |
| `metadata` | optional array |

**Success (201):**

```json
{
  "message": "Booking created successfully",
  "data": {
    "booking_ref": "BK-AB12CD34",
    "resource_id": 1,
    "status": "confirmed",
    "starts_at": "2026-07-01T10:00:00.000000Z",
    "ends_at": "2026-07-01T12:00:00.000000Z"
  }
}
```

**Conflict (500)** — overlapping slot:

```json
{
  "message": "Failed to create booking.",
  "error": "Resource already booked."
}
```

### Checkout a booking

```bash
curl -X POST http://127.0.0.1:8000/api/bookings/BK-AB12CD34/checkout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

On success, the `BookingCheckedOut` event fires and `PublishCheckoutToBroker` logs a payload (ready to wire to RabbitMQ, Kafka, Redis Streams, AWS SNS, etc.).

**Event payload (logged):**

```json
{
  "booking_ref": "BK-AB12CD34",
  "user": 1,
  "amount": 50000,
  "checked_out_at": "2026-06-27T18:30:00.000000Z"
}
```

View logs:

```bash
php artisan pail
# or
tail -f storage/logs/laravel.log
```

## Message Broker Integration

The checkout listener (`PublishCheckoutToBroker`) currently logs the payload. To connect a real broker:

1. Open `app/Listeners/PublishCheckoutToBroker.php`
2. Replace the `Log::info()` call with your broker client (RabbitMQ, Kafka, etc.)
3. Optionally implement `ShouldQueue` on the listener for async publishing

Event registration is in `AppServiceProvider`:

```php
Event::listen(BookingCheckedOut::class, PublishCheckoutToBroker::class);
```

## Running Tests

```bash
composer test
# or
php artisan test
```

## License

MIT

# Webhooks

Webhooks allow you to receive HTTP callbacks when specific events occur in Shrinkr. Instead of polling the API, your application can receive real-time notifications.

## Table of Contents

- [Configuration](#configuration)
- [Available Events](#available-events)
- [Webhook Endpoints](#webhook-endpoints)
- [Webhook Payload](#webhook-payload)
- [Security & Verification](#security--verification)
- [Retry Logic](#retry-logic)

## Configuration

Configure webhooks in `config/shrinkr.php`:

```php
'webhooks' => [
    // Enable or disable webhook functionality
    'enabled' => true,

    // Middleware for webhook management endpoints
    'middleware' => ['api', 'auth:sanctum'],

    // Secret key for webhook signature verification (HMAC-SHA256)
    'secret' => env('SHRINKR_WEBHOOK_SECRET'),

    // Maximum retry attempts for failed webhook deliveries
    'max_retries' => 3,

    // Timeout for webhook HTTP requests (in seconds)
    'timeout' => 10,

    // Events that can trigger webhooks
    'events' => [
        'url.accessed',
        'url.expired',
        'url.created',
        'url.updated',
        'url.deleted',
    ],
],
```

### Environment Variables

Add to your `.env`:

```env
SHRINKR_WEBHOOK_SECRET=your-random-secret-key-here
```

Generate a secure secret:

```bash
php artisan tinker
>>> bin2hex(random_bytes(32))
```

## Available Events

| Event | Description | Triggered When |
|-------|-------------|----------------|
| `url.created` | URL created | New shortened URL is created via API |
| `url.updated` | URL updated | URL is modified via API |
| `url.deleted` | URL deleted | URL is removed via API |
| `url.accessed` | URL accessed | Someone clicks a shortened URL |
| `url.expired` | URL expired | URL expires (time-based or health check) |

## Webhook Endpoints

### List Webhooks

```http
GET /api/shrinkr/webhooks
```

### Create Webhook

```http
POST /api/shrinkr/webhooks
```

**Request Body:**

```json
{
    "url": "https://your-app.com/webhooks/shrinkr",
    "events": ["url.created", "url.accessed"],
    "user_id": 1,
    "is_active": true
}
```

**Response:**

```json
{
    "data": {
        "id": 1,
        "uuid": "9c8e7f6e-5d4c-3b2a-1f0e-9d8c7b6a5f4e",
        "user_id": 1,
        "url": "https://your-app.com/webhooks/shrinkr",
        "events": ["url.created", "url.accessed"],
        "is_active": true,
        "created_at": "2025-01-15T10:30:00+00:00",
        "updated_at": "2025-01-15T10:30:00+00:00",
        "last_triggered_at": null
    }
}
```

### Get Webhook

```http
GET /api/shrinkr/webhooks/{id}
```

### Update Webhook

```http
PATCH /api/shrinkr/webhooks/{id}
```

### Delete Webhook

```http
DELETE /api/shrinkr/webhooks/{id}
```

### Test Webhook

Send a test webhook delivery:

```http
POST /api/shrinkr/webhooks/{id}/test
```

### Get Webhook Calls

View delivery history:

```http
GET /api/shrinkr/webhooks/{id}/calls
```

## Webhook Payload

When an event occurs, Shrinkr sends a POST request to your webhook URL.

### Headers

```
Content-Type: application/json
X-Shrinkr-Signature: sha256_hmac_signature
X-Shrinkr-Event: url.created
X-Shrinkr-Delivery-ID: 9c8e7f6e-5d4c-3b2a-1f0e-9d8c7b6a5f4e
User-Agent: Shrinkr-Webhook/1.0
```

### Example Payload (url.created)

```json
{
    "event": "url.created",
    "timestamp": "2025-01-15T10:30:00+00:00",
    "data": {
        "id": 1,
        "uuid": "9c8e7f6e-5d4c-3b2a-1f0e-9d8c7b6a5f4e",
        "user_id": 1,
        "original_url": "https://example.com/long-url",
        "shortened_url": "abc123",
        "custom_slug": "my-link",
        "is_expired": false,
        "expires_at": null,
        "created_at": "2025-01-15T10:30:00+00:00",
        "updated_at": "2025-01-15T10:30:00+00:00"
    }
}
```

### Example Payload (url.accessed)

```json
{
    "event": "url.accessed",
    "timestamp": "2025-01-15T10:35:00+00:00",
    "data": {
        "id": 1,
        "uuid": "9c8e7f6e-5d4c-3b2a-1f0e-9d8c7b6a5f4e",
        "original_url": "https://example.com/long-url",
        "shortened_url": "abc123",
        "access_metadata": {}
    }
}
```

## Security & Verification

### Verify Webhook Signature

All webhook deliveries include an `X-Shrinkr-Signature` header containing an HMAC-SHA256 signature.

**Laravel Example:**

```php
use Illuminate\Support\Facades\Hash;

public function handleWebhook(Request $request)
{
    $signature = $request->header('X-Shrinkr-Signature');
    $payload = $request->getContent();
    $secret = config('shrinkr.webhooks.secret');

    $expectedSignature = hash_hmac('sha256', $payload, $secret);

    if (! hash_equals($expectedSignature, $signature)) {
        abort(403, 'Invalid signature');
    }

    // Process webhook...
}
```

**Node.js Example:**

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secret) {
    const expectedSignature = crypto
        .createHmac('sha256', secret)
        .update(JSON.stringify(payload))
        .digest('hex');

    return crypto.timingSafeEqual(
        Buffer.from(signature),
        Buffer.from(expectedSignature)
    );
}
```

## Retry Logic

### Automatic Retries

Failed webhook deliveries are automatically retried using exponential backoff:

1. **1st retry:** 1 minute after failure
2. **2nd retry:** 5 minutes after failure
3. **3rd retry:** 15 minutes after failure

After 3 failed attempts, the webhook call is marked as permanently failed.

### Manual Retry

Retry failed webhooks manually:

```bash
php artisan shrinkr:retry-webhooks
```

Force retry all pending webhooks:

```bash
php artisan shrinkr:retry-webhooks --force
```

### Schedule Automatic Retries

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('shrinkr:retry-webhooks')->everyFiveMinutes();
}
```

## Handling Webhooks

### Laravel Controller Example

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShrinkrWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify signature
        $this->verifySignature($request);

        // Get event type
        $event = $request->header('X-Shrinkr-Event');

        // Handle different events
        match ($event) {
            'url.created' => $this->handleUrlCreated($request->all()),
            'url.accessed' => $this->handleUrlAccessed($request->all()),
            'url.expired' => $this->handleUrlExpired($request->all()),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    protected function handleUrlCreated(array $payload)
    {
        // Your logic here
        logger()->info('URL created', $payload);
    }

    protected function handleUrlAccessed(array $payload)
    {
        // Your logic here
        logger()->info('URL accessed', $payload);
    }

    protected function handleUrlExpired(array $payload)
    {
        // Send notification, update database, etc.
    }

    protected function verifySignature(Request $request)
    {
        $signature = $request->header('X-Shrinkr-Signature');
        $payload = $request->getContent();
        $secret = config('shrinkr.webhooks.secret');

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            abort(403, 'Invalid signature');
        }
    }
}
```

## Best Practices

1. **Always verify signatures** - Prevent unauthorized webhook calls
2. **Respond quickly** - Return a 200 response within 10 seconds
3. **Process async** - Queue webhook processing for heavy operations
4. **Log everything** - Keep logs of webhook calls for debugging
5. **Handle duplicates** - Use `X-Shrinkr-Delivery-ID` to detect duplicate deliveries
6. **Test webhooks** - Use the test endpoint before going live

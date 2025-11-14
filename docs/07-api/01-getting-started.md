# API - Getting Started

Shrinkr provides a comprehensive RESTful API for programmatic access to all URL shortening functionality. The API allows you to create, read, update, and delete shortened URLs, access analytics, and manage webhooks.

## Table of Contents

- [Configuration](#configuration)
- [Authentication](#authentication)
- [API Prefix](#api-prefix)
- [Response Format](#response-format)
- [Error Handling](#error-handling)

## Configuration

The API is configurable via `config/shrinkr.php`:

```php
'api' => [
    // Enable or disable API routes
    'enabled' => true,

    // API route prefix (e.g., /api/shrinkr/urls)
    'prefix' => 'api/shrinkr',

    // Middleware applied to API routes
    'middleware' => ['api'],

    // Route name prefix for API routes
    'route_name_prefix' => 'shrinkr.api',
],
```

## Authentication

**Important:** Shrinkr does not include built-in authentication. As a package, it allows your application to handle authentication and authorization.

### Adding Authentication

To secure your API endpoints, add authentication middleware in your config:

```php
// config/shrinkr.php
'api' => [
    'middleware' => ['api', 'auth:sanctum'], // Add your auth middleware
],
```

### Recommended Approach with Laravel Sanctum

1. Install Laravel Sanctum:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

2. Update your config:

```php
'api' => [
    'middleware' => ['api', 'auth:sanctum', 'throttle:60,1'],
],
```

3. Generate tokens for API access:

```php
$user = User::find(1);
$token = $user->createToken('api-token')->plainTextToken;
```

4. Use the token in requests:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     https://your-app.com/api/shrinkr/urls
```

## API Prefix

By default, all API routes are prefixed with `api/shrinkr`. You can customize this:

```php
'api' => [
    'prefix' => 'v1/shrinkr', // Custom prefix
],
```

This will make your endpoints available at:

- `https://your-app.com/v1/shrinkr/urls`
- `https://your-app.com/v1/shrinkr/urls/{id}/analytics`

## Response Format

All API responses follow a consistent JSON format using Laravel API Resources.

### Single Resource Response

```json
{
    "data": {
        "id": 1,
        "uuid": "9c8e7f6e-5d4c-3b2a-1f0e-9d8c7b6a5f4e",
        "user_id": 1,
        "original_url": "https://example.com/very-long-url",
        "shortened_url": "abc123",
        "custom_slug": null,
        "is_expired": false,
        "expires_at": null,
        "created_at": "2025-01-15T10:30:00+00:00",
        "updated_at": "2025-01-15T10:30:00+00:00",
        "full_shortened_url": "https://your-app.com/s/abc123"
    }
}
```

### Collection Response

```json
{
    "data": [
        {
            "id": 1,
            "uuid": "...",
            "original_url": "...",
            ...
        },
        {
            "id": 2,
            ...
        }
    ],
    "meta": {
        "total": 50,
        "per_page": 15,
        "current_page": 1,
        "last_page": 4,
        "from": 1,
        "to": 15
    },
    "links": {
        "first": "https://your-app.com/api/shrinkr/urls?page=1",
        "last": "https://your-app.com/api/shrinkr/urls?page=4",
        "prev": null,
        "next": "https://your-app.com/api/shrinkr/urls?page=2"
    }
}
```

## Error Handling

### Validation Errors (422)

```json
{
    "message": "The original url field is required.",
    "errors": {
        "original_url": [
            "The original url field is required."
        ]
    }
}
```

### Not Found (404)

```json
{
    "message": "No query results for model [CleaniqueCoders\\Shrinkr\\Models\\Url]."
}
```

### Unauthorized (401)

```json
{
    "message": "Unauthenticated."
}
```

### Server Error (500)

```json
{
    "message": "Server Error"
}
```

## Rate Limiting

You can configure rate limiting per your application's needs:

```php
'api' => [
    'middleware' => ['api', 'throttle:100,1'], // 100 requests per minute
],
```

## Next Steps

- [URL Endpoints](02-url-endpoints.md) - Learn about URL CRUD operations
- [Analytics Endpoints](03-analytics-endpoints.md) - Access click analytics
- [Webhook Endpoints](04-webhook-endpoints.md) - Manage webhooks

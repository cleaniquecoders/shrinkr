# API - URL Endpoints

Complete reference for URL management via the API.

## Endpoints Overview

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/shrinkr/urls` | List all URLs |
| POST | `/api/shrinkr/urls` | Create a new shortened URL |
| GET | `/api/shrinkr/urls/{id}` | Get a single URL |
| PATCH | `/api/shrinkr/urls/{id}` | Update a URL |
| DELETE | `/api/shrinkr/urls/{id}` | Delete a URL |
| GET | `/api/shrinkr/urls/stats` | Get URL statistics |

## List URLs

Get a paginated list of shortened URLs.

```http
GET /api/shrinkr/urls
```

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `user_id` | integer | Filter by user ID |
| `is_expired` | boolean | Filter by expiration status |
| `search` | string | Search in URLs and slugs |
| `order_by` | string | Sort field (default: `created_at`) |
| `order_direction` | string | Sort direction (`asc`/`desc`, default: `desc`) |
| `per_page` | integer | Items per page (max: 100, default: 15) |

### Example Request

```bash
curl -X GET "https://your-app.com/api/shrinkr/urls?user_id=1&per_page=20" \
     -H "Authorization: Bearer YOUR_TOKEN"
```

### Example Response

```json
{
    "data": [
        {
            "id": 1,
            "uuid": "9c8e7f6e-5d4c-3b2a-1f0e-9d8c7b6a5f4e",
            "user_id": 1,
            "original_url": "https://example.com/long-url",
            "shortened_url": "abc123",
            "custom_slug": "my-link",
            "is_expired": false,
            "expires_at": null,
            "recheck_at": null,
            "created_at": "2025-01-15T10:30:00+00:00",
            "updated_at": "2025-01-15T10:30:00+00:00",
            "full_shortened_url": "https://your-app.com/s/abc123"
        }
    ],
    "meta": {
        "total": 50,
        "per_page": 20,
        "current_page": 1,
        "last_page": 3
    },
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    }
}
```

## Create URL

Create a new shortened URL.

```http
POST /api/shrinkr/urls
```

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `original_url` | string | Yes | The URL to shorten (max: 2048 chars) |
| `custom_slug` | string | No | Custom slug (3-255 chars, alphanumeric) |
| `expiry_duration` | integer | No | Expiry duration in minutes |
| `user_id` | integer | No | Associated user ID |

### Example Request

```bash
curl -X POST "https://your-app.com/api/shrinkr/urls" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
         "original_url": "https://example.com/very-long-url-to-shorten",
         "custom_slug": "my-custom-link",
         "expiry_duration": 1440,
         "user_id": 1
     }'
```

### Example Response (201 Created)

```json
{
    "data": {
        "id": 1,
        "uuid": "9c8e7f6e-5d4c-3b2a-1f0e-9d8c7b6a5f4e",
        "user_id": 1,
        "original_url": "https://example.com/very-long-url-to-shorten",
        "shortened_url": "my-custom-link",
        "custom_slug": "my-custom-link",
        "is_expired": false,
        "expires_at": "2025-01-16T10:30:00+00:00",
        "created_at": "2025-01-15T10:30:00+00:00",
        "updated_at": "2025-01-15T10:30:00+00:00",
        "full_shortened_url": "https://your-app.com/s/my-custom-link"
    }
}
```

## Get Single URL

Retrieve a specific URL by ID, UUID, or shortened URL.

```http
GET /api/shrinkr/urls/{id}
```

### Path Parameters

| Parameter | Description |
|-----------|-------------|
| `id` | URL ID, UUID, or shortened URL slug |

### Example Request

```bash
curl -X GET "https://your-app.com/api/shrinkr/urls/1" \
     -H "Authorization: Bearer YOUR_TOKEN"
```

## Update URL

Update an existing shortened URL.

```http
PATCH /api/shrinkr/urls/{id}
PUT /api/shrinkr/urls/{id}
```

### Request Body

| Field | Type | Description |
|-------|------|-------------|
| `original_url` | string | Update the original URL |
| `custom_slug` | string | Update the custom slug |
| `expiry_duration` | integer | Update expiry duration (minutes from now) |
| `is_expired` | boolean | Manually mark as expired/active |

### Example Request

```bash
curl -X PATCH "https://your-app.com/api/shrinkr/urls/1" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
         "original_url": "https://example.com/updated-url",
         "expiry_duration": 2880
     }'
```

## Delete URL

Delete a shortened URL.

```http
DELETE /api/shrinkr/urls/{id}
```

### Example Request

```bash
curl -X DELETE "https://your-app.com/api/shrinkr/urls/1" \
     -H "Authorization: Bearer YOUR_TOKEN"
```

### Example Response

```json
{
    "message": "URL deleted successfully"
}
```

## URL Statistics

Get aggregate statistics for URLs.

```http
GET /api/shrinkr/urls/stats
```

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `user_id` | integer | Filter stats by user ID |

### Example Request

```bash
curl -X GET "https://your-app.com/api/shrinkr/urls/stats?user_id=1" \
     -H "Authorization: Bearer YOUR_TOKEN"
```

### Example Response

```json
{
    "total_urls": 150,
    "active_urls": 120,
    "expired_urls": 30,
    "urls_with_custom_slug": 45,
    "urls_with_expiry": 60
}
```

## Error Responses

### Validation Error (422)

```json
{
    "message": "The custom slug has already been taken.",
    "errors": {
        "custom_slug": [
            "The custom slug has already been taken."
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

# Facades

The Shrinkr facade provides a convenient interface for URL shortening operations.

## Overview

The `Shrinkr` facade is the primary interface for interacting with the package. It provides static methods that proxy to the underlying `Shrinkr` class.

## Namespace

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
```

## Available Methods

### shorten()

Create a shortened URL.

**Signature:**

```php
public static function shorten(
    string $url,
    int $userId,
    array $options = []
): Url
```

**Parameters:**

- `$url` (string) - The original URL to shorten
- `$userId` (int) - The ID of the user creating the short URL
- `$options` (array) - Optional configuration:
  - `custom_slug` (string|null) - Custom slug for the shortened URL
  - `expiry_duration` (int|null) - Expiry duration in minutes

**Returns:** `Url` - The created URL model instance

**Throws:** `SlugAlreadyExistsException` - If the custom slug already exists

**Example:**

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

// Basic usage
$url = Shrinkr::shorten('https://example.com', auth()->id());

// With custom slug
$url = Shrinkr::shorten('https://example.com', auth()->id(), [
    'custom_slug' => 'my-link',
]);

// With expiry
$url = Shrinkr::shorten('https://example.com', auth()->id(), [
    'expiry_duration' => 60, // 60 minutes
]);

// With both
$url = Shrinkr::shorten('https://example.com', auth()->id(), [
    'custom_slug' => 'promo',
    'expiry_duration' => 1440, // 24 hours
]);
```

### resolve()

Retrieve the original URL from a shortened slug.

**Signature:**

```php
public static function resolve(string $slug): ?string
```

**Parameters:**

- `$slug` (string) - The shortened URL slug

**Returns:** `string|null` - The original URL or null if not found

**Example:**

```php
$originalUrl = Shrinkr::resolve('abc123');

if ($originalUrl) {
    return redirect($originalUrl);
}

abort(404, 'URL not found');
```

**Note:** This method automatically tracks the visit and dispatches the `UrlAccessed` event.

### update()

Update an existing shortened URL.

**Signature:**

```php
public static function update(Url $url, array $options = []): Url
```

**Parameters:**

- `$url` (Url) - The URL model instance to update
- `$options` (array) - Update options:
  - `custom_slug` (string|null) - New custom slug
  - `expiry_duration` (int|null) - New expiry duration in minutes

**Returns:** `Url` - The updated URL model instance

**Throws:** `SlugAlreadyExistsException` - If the new slug already exists

**Example:**

```php
use CleaniqueCoders\Shrinkr\Models\Url;

$url = Url::find(1);

$updated = Shrinkr::update($url, [
    'custom_slug' => 'new-slug',
    'expiry_duration' => 120,
]);
```

### delete()

Delete a shortened URL.

**Signature:**

```php
public static function delete(Url $url): bool
```

**Parameters:**

- `$url` (Url) - The URL model instance to delete

**Returns:** `bool` - True if successful

**Example:**

```php
$url = Url::find(1);
Shrinkr::delete($url);
```

## Usage Patterns

### Controller Integration

```php
namespace App\Http\Controllers;

use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use Illuminate\Http\Request;

class UrlController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'custom_slug' => 'nullable|string',
            'expiry_minutes' => 'nullable|integer',
        ]);

        $url = Shrinkr::shorten(
            $validated['url'],
            auth()->id(),
            [
                'custom_slug' => $validated['custom_slug'] ?? null,
                'expiry_duration' => $validated['expiry_minutes'] ?? null,
            ]
        );

        return response()->json([
            'shortened_url' => $url->shortened_url,
            'slug' => $url->slug,
        ]);
    }

    public function redirect(string $slug)
    {
        $originalUrl = Shrinkr::resolve($slug);

        if (!$originalUrl) {
            abort(404);
        }

        return redirect($originalUrl);
    }
}
```

### Service Class

```php
namespace App\Services;

use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

class CampaignUrlService
{
    public function createCampaignUrl(string $url, string $campaign): string
    {
        $shortUrl = Shrinkr::shorten($url, auth()->id(), [
            'custom_slug' => "campaign-{$campaign}",
            'expiry_duration' => 43200, // 30 days
        ]);

        return $shortUrl->shortened_url;
    }
}
```

## Facade vs Direct Class Usage

You can use either the facade or the underlying class directly:

**Using Facade:**

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

$url = Shrinkr::shorten('https://example.com', 1);
```

**Using Class Directly:**

```php
use CleaniqueCoders\Shrinkr\Shrinkr;

$shrinkr = new Shrinkr();
$url = $shrinkr->shorten('https://example.com', 1);
```

**Using Dependency Injection:**

```php
use CleaniqueCoders\Shrinkr\Shrinkr;

public function __construct(
    private Shrinkr $shrinkr
) {}

public function createUrl(string $url)
{
    return $this->shrinkr->shorten($url, auth()->id());
}
```

## Testing with Facades

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use CleaniqueCoders\Shrinkr\Models\Url;

test('can shorten url via facade', function () {
    $url = Shrinkr::shorten('https://example.com', 1);

    expect($url)->toBeInstanceOf(Url::class)
        ->and($url->original_url)->toBe('https://example.com');
});
```

## Next Steps

- [Actions](02-actions.md) - Direct action class usage
- [Models](03-models.md) - URL and RedirectLog models
- [Usage Examples](../03-usage/01-shortening-urls.md) - Practical examples

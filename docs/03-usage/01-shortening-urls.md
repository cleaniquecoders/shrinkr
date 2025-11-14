# Shortening URLs

Learn how to create shortened URLs with Shrinkr using various methods and options.

## Using the Facade

The simplest way to shorten URLs is using the Shrinkr facade.

### Basic Shortening

Create a shortened URL with an auto-generated slug:

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

$shortUrl = Shrinkr::shorten('https://example.com/very-long-url', auth()->id());
// Returns: https://yourdomain.com/s/abc123
```

### With Custom Slug

Specify a custom slug for branding or readability:

```php
$shortUrl = Shrinkr::shorten('https://example.com/product', auth()->id(), [
    'custom_slug' => 'summer-sale',
]);
// Returns: https://yourdomain.com/s/summer-sale
```

**Important:** Custom slugs must be unique. If the slug exists, a `SlugAlreadyExistsException` will be thrown.

### With Expiry Duration

Create a URL that expires after a specified number of minutes:

```php
$shortUrl = Shrinkr::shorten('https://example.com/limited-offer', auth()->id(), [
    'expiry_duration' => 60, // Expires in 60 minutes
]);
```

**Expiry Options:**

```php
// 1 hour
'expiry_duration' => 60

// 24 hours (1 day)
'expiry_duration' => 1440

// 7 days
'expiry_duration' => 10080

// 30 days
'expiry_duration' => 43200
```

### Combined Options

Combine custom slug and expiry:

```php
$shortUrl = Shrinkr::shorten('https://example.com/flash-sale', auth()->id(), [
    'custom_slug' => 'flash24',
    'expiry_duration' => 1440, // 24 hours
]);
```

## Using the Action Class

For more control, use the `CreateShortUrlAction` directly:

```php
use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;

$action = new CreateShortUrlAction();

$url = $action->execute(
    url: 'https://example.com/long-url',
    userId: auth()->id(),
    options: [
        'custom_slug' => 'my-link',
        'expiry_duration' => 60,
    ]
);

// Access properties
echo $url->shortened_url;
echo $url->slug;
echo $url->expires_at;
```

## Error Handling

### Slug Already Exists

```php
use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;

try {
    $url = Shrinkr::shorten('https://example.com', auth()->id(), [
        'custom_slug' => 'existing-slug',
    ]);
} catch (SlugAlreadyExistsException $e) {
    // Handle duplicate slug
    $url = Shrinkr::shorten('https://example.com', auth()->id(), [
        'custom_slug' => 'existing-slug-2',
    ]);
}
```

### Invalid URL

```php
try {
    $url = Shrinkr::shorten('not-a-valid-url', auth()->id());
} catch (\InvalidArgumentException $e) {
    return back()->withErrors(['url' => 'Please provide a valid URL']);
}
```

## Common Patterns

### Marketing Campaigns

```php
$campaigns = [
    'summer' => 'https://mysite.com/summer-sale',
    'winter' => 'https://mysite.com/winter-sale',
    'spring' => 'https://mysite.com/spring-sale',
];

foreach ($campaigns as $slug => $url) {
    Shrinkr::shorten($url, auth()->id(), [
        'custom_slug' => $slug . '-2024',
        'expiry_duration' => 43200, // 30 days
    ]);
}
```

### Social Media Sharing

```php
$post = BlogPost::find($id);

$socialUrl = Shrinkr::shorten($post->url, $post->user_id, [
    'custom_slug' => $post->slug,
]);

// Share on social media
Share::page($post->url)
    ->facebook()
    ->twitter()
    ->linkedin()
    ->telegram()
    ->whatsapp()
    ->getRawLinks();
```

### Temporary Download Links

```php
$downloadUrl = Shrinkr::shorten(
    url: "https://mysite.com/downloads/{$file->id}",
    userId: auth()->id(),
    options: [
        'expiry_duration' => 60, // 1 hour
    ]
);

// Email to user
Mail::to($user)->send(new DownloadLinkEmail($downloadUrl));
```

### QR Code Generation

```php
$url = Shrinkr::shorten('https://mysite.com/menu', auth()->id(), [
    'custom_slug' => 'restaurant-menu',
]);

// Generate QR code using SimpleSoftwareIO/simple-qrcode
$qrCode = QrCode::size(300)
    ->generate($url->shortened_url);

return view('qr', compact('qrCode'));
```

## Bulk URL Shortening

```php
$urls = [
    'https://example.com/page1',
    'https://example.com/page2',
    'https://example.com/page3',
];

$shortenedUrls = collect($urls)->map(function ($url) {
    return Shrinkr::shorten($url, auth()->id());
});

// With custom slugs
$urlsWithSlugs = [
    'page1' => 'https://example.com/page1',
    'page2' => 'https://example.com/page2',
    'page3' => 'https://example.com/page3',
];

$shortenedUrls = collect($urlsWithSlugs)->map(function ($url, $slug) {
    return Shrinkr::shorten($url, auth()->id(), [
        'custom_slug' => $slug,
    ]);
});
```

## Controller Example

```php
<?php

namespace App\Http\Controllers;

use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
use Illuminate\Http\Request;

class UrlController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'custom_slug' => 'nullable|string|alpha_dash|max:255',
            'expiry_duration' => 'nullable|integer|min:1',
        ]);

        try {
            $shortUrl = Shrinkr::shorten(
                $validated['url'],
                auth()->id(),
                [
                    'custom_slug' => $validated['custom_slug'] ?? null,
                    'expiry_duration' => $validated['expiry_duration'] ?? null,
                ]
            );

            return response()->json([
                'success' => true,
                'shortened_url' => $shortUrl,
                'slug' => $shortUrl->slug,
            ]);

        } catch (SlugAlreadyExistsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'This custom slug is already taken.',
            ], 422);
        }
    }
}
```

## API Endpoint Example

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/shorten', [ApiUrlController::class, 'shorten']);
});

// app/Http/Controllers/Api/ApiUrlController.php
class ApiUrlController extends Controller
{
    public function shorten(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'custom_slug' => 'nullable|alpha_dash',
            'expiry_minutes' => 'nullable|integer',
        ]);

        $url = Shrinkr::shorten(
            $validated['url'],
            $request->user()->id,
            [
                'custom_slug' => $validated['custom_slug'] ?? null,
                'expiry_duration' => $validated['expiry_minutes'] ?? null,
            ]
        );

        return response()->json([
            'data' => [
                'original_url' => $url->original_url,
                'shortened_url' => $url->shortened_url,
                'slug' => $url->slug,
                'expires_at' => $url->expires_at?->toIso8601String(),
            ],
        ]);
    }
}
```

## Next Steps

- [Resolving URLs](02-resolving-urls.md) - Access and redirect shortened URLs
- [Updating URLs](03-updating-urls.md) - Modify existing URLs
- [Analytics](../04-features/02-analytics.md) - Track URL performance

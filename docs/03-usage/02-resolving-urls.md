# Resolving URLs

Learn how to resolve shortened URLs and handle redirects.

## Using the Facade

### Basic Resolution

Retrieve the original URL from a shortened slug:

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

$originalUrl = Shrinkr::resolve('abc123');
// Returns: https://example.com/long-url
```

If the slug doesn't exist, `null` is returned.

### With Redirect

In a controller or route, redirect to the original URL:

```php
public function redirect($slug)
{
    $originalUrl = Shrinkr::resolve($slug);

    if (!$originalUrl) {
        abort(404, 'Short URL not found');
    }

    return redirect($originalUrl);
}
```

## Automatic Tracking

When `resolve()` is called, Shrinkr automatically:

1. Tracks the visit in logs
2. Records visitor information (IP, browser, platform, device)
3. Captures referrer and request details
4. Dispatches the `UrlAccessed` event
5. Increments the visits counter

## Handling Expired URLs

```php
use CleaniqueCoders\Shrinkr\Models\Url;

public function redirect($slug)
{
    $url = Url::where('slug', $slug)->first();

    if (!$url) {
        abort(404, 'URL not found');
    }

    // Check if expired
    if ($url->expires_at && $url->expires_at->isPast()) {
        return view('errors.url-expired', compact('url'));
    }

    return redirect($url->original_url);
}
```

## Using the Redirect Controller

Shrinkr includes a `RedirectController` that handles redirects automatically. The route is registered as:

```
GET /{slug} -> RedirectController
```

### Default Behavior

When a user visits a shortened URL:

1. The slug is resolved
2. Analytics are tracked
3. Events are dispatched
4. User is redirected to the original URL

### Custom Error Pages

Create custom error views for invalid URLs:

```blade
{{-- resources/views/errors/404.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="error-page">
    <h1>URL Not Found</h1>
    <p>The shortened URL you're looking for doesn't exist.</p>
    <a href="{{ url('/') }}">Go Home</a>
</div>
@endsection
```

## Resolving with Model

Get the full URL model with all details:

```php
use CleaniqueCoders\Shrinkr\Models\Url;

$url = Url::where('slug', 'abc123')->first();

if ($url) {
    echo $url->original_url;
    echo $url->shortened_url;
    echo $url->slug;
    echo $url->user_id;
    echo $url->created_at;
    echo $url->expires_at;
}
```

## Handling Events

Listen to the `UrlAccessed` event when URLs are resolved:

```php
// app/Listeners/TrackUrlAccess.php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use Illuminate\Support\Facades\Log;

class TrackUrlAccess
{
    public function handle(UrlAccessed $event): void
    {
        $url = $event->url;
        $request = $event->request;

        Log::info("URL accessed", [
            'slug' => $url->slug,
            'original_url' => $url->original_url,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Send to analytics service
        Analytics::track('url_accessed', [
            'url_id' => $url->id,
            'timestamp' => now(),
        ]);
    }
}
```

Register the listener in `EventServiceProvider`:

```php
use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use App\Listeners\TrackUrlAccess;

protected $listen = [
    UrlAccessed::class => [
        TrackUrlAccess::class,
    ],
];
```

## Rate Limiting

Apply rate limiting to prevent abuse:

```php
// config/shrinkr.php
'middleware' => ['throttle:60,1'], // 60 requests per minute
```

Custom rate limiting by user:

```php
public function redirect($slug)
{
    $key = 'url-redirect:' . request()->ip();

    if (RateLimiter::tooManyAttempts($key, 100)) {
        $seconds = RateLimiter::availableIn($key);
        abort(429, "Too many requests. Try again in {$seconds} seconds.");
    }

    RateLimiter::hit($key, 60); // 60 seconds

    $originalUrl = Shrinkr::resolve($slug);
    return redirect($originalUrl);
}
```

## Redirect with Custom Headers

Add custom headers to redirects:

```php
public function redirect($slug)
{
    $originalUrl = Shrinkr::resolve($slug);

    if (!$originalUrl) {
        abort(404);
    }

    return redirect($originalUrl)
        ->header('X-Shortened-By', 'Shrinkr')
        ->header('X-Original-URL', $originalUrl);
}
```

## Conditional Redirects

Redirect based on conditions:

```php
public function redirect($slug, Request $request)
{
    $url = Url::where('slug', $slug)->first();

    if (!$url) {
        abort(404);
    }

    // Mobile vs Desktop
    if ($request->header('User-Agent')) {
        $agent = new Agent();

        if ($agent->isMobile()) {
            return redirect($url->mobile_url ?? $url->original_url);
        }
    }

    // By Country
    $country = geoip($request->ip())->country;

    if ($country === 'US') {
        return redirect($url->us_url ?? $url->original_url);
    }

    return redirect($url->original_url);
}
```

## Previewing URLs

Show a preview page before redirecting:

```php
public function preview($slug)
{
    $url = Url::where('slug', $slug)->first();

    if (!$url) {
        abort(404);
    }

    return view('url.preview', compact('url'));
}

public function confirm($slug)
{
    $originalUrl = Shrinkr::resolve($slug);

    return redirect($originalUrl);
}
```

```blade
{{-- resources/views/url/preview.blade.php --}}
<div class="preview">
    <h2>You're about to visit:</h2>
    <p>{{ $url->original_url }}</p>

    <a href="{{ route('url.confirm', $url->slug) }}">
        Continue to URL
    </a>
</div>
```

## Testing Resolution

```php
// tests/Feature/UrlResolutionTest.php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use CleaniqueCoders\Shrinkr\Models\Url;

test('can resolve shortened url', function () {
    $url = Url::factory()->create([
        'slug' => 'test123',
        'original_url' => 'https://example.com',
    ]);

    $originalUrl = Shrinkr::resolve('test123');

    expect($originalUrl)->toBe('https://example.com');
});

test('returns null for non-existent slug', function () {
    $originalUrl = Shrinkr::resolve('nonexistent');

    expect($originalUrl)->toBeNull();
});

test('redirects to original url', function () {
    $url = Url::factory()->create([
        'slug' => 'redirect-test',
        'original_url' => 'https://example.com/target',
    ]);

    $response = $this->get(route('shrnkr.redirect', ['slug' => 'redirect-test']));

    $response->assertRedirect('https://example.com/target');
});
```

## Next Steps

- [Updating URLs](03-updating-urls.md) - Modify existing shortened URLs
- [Analytics](../04-features/02-analytics.md) - View tracking data
- [Events](../04-features/03-events.md) - Handle URL access events

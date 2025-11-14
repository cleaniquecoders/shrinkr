# Routing Configuration

This guide covers how to configure URL routing, middleware, and domains for Shrinkr.

## Route Structure

Shrinkr registers a single route for handling URL redirects:

```
{domain}/{prefix}/{slug}
```

**Example:** `https://yourdomain.com/s/abc123`

## URL Prefix

Configure the prefix for all shortened URLs:

```php
'prefix' => 's',
```

**Common Prefixes:**

```php
'prefix' => 's',      // https://yourdomain.com/s/abc123
'prefix' => 'go',     // https://yourdomain.com/go/abc123
'prefix' => 'link',   // https://yourdomain.com/link/abc123
'prefix' => '',       // https://yourdomain.com/abc123 (root level)
```

## Domain Configuration

### Any Domain (Default)

Allow shortened URLs to work on any domain:

```php
'domain' => null,
```

This is useful for multi-domain applications or when you don't want to restrict domains.

### Specific Domain

Bind shortened URLs to a specific domain:

```php
'domain' => 'bite.ly',
```

**Result:** URLs only work on `https://bite.ly/s/{slug}`

### Subdomain Configuration

Use a subdomain for shortened URLs:

```php
'domain' => 'go.yourdomain.com',
'prefix' => '',
```

**Result:** URLs like `https://go.yourdomain.com/abc123`

## Middleware Configuration

### Default Middleware

The default configuration includes rate limiting:

```php
'middleware' => ['throttle:60,1'],
```

This allows 60 requests per minute per IP address.

### Rate Limiting Options

**Higher Rate Limit:**

```php
'middleware' => ['throttle:100,1'],  // 100 requests/minute
```

**Lower Rate Limit:**

```php
'middleware' => ['throttle:30,1'],   // 30 requests/minute
```

**Multiple Time Windows:**

```php
'middleware' => ['throttle:1000,60'], // 1000 requests per hour
```

### Authentication Middleware

Require authentication to create shortened URLs:

```php
'middleware' => ['auth', 'throttle:60,1'],
```

**Note:** This applies to the redirect route. For protecting URL creation, implement at the controller level.

### Multiple Middleware

Combine multiple middleware:

```php
'middleware' => [
    'auth',
    'verified',
    'throttle:600,1',
    'custom-middleware',
],
```

### Custom Middleware

Create custom middleware for additional security or logging:

```php
// app/Http/Middleware/TrackShrinkrAccess.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackShrinkrAccess
{
    public function handle(Request $request, Closure $next)
    {
        // Custom tracking logic
        \Log::info('Shrinkr access from: ' . $request->ip());

        return $next($request);
    }
}
```

Register and use:

```php
'middleware' => ['throttle:60,1', 'track-shrinkr-access'],
```

## Route Naming

### Default Route Name

```php
'route-name' => 'shrnkr.redirect',
```

### Using Route Name

Generate URLs programmatically:

```php
$url = route('shrnkr.redirect', ['slug' => 'abc123']);
// Outputs: https://yourdomain.com/s/abc123
```

Check if route exists:

```php
if (Route::has('shrnkr.redirect')) {
    // Route is registered
}
```

### Custom Route Name

Change the route name if it conflicts with existing routes:

```php
'route-name' => 'short.link',
```

## Custom Controller

### Default Controller

```php
'controller' => \CleaniqueCoders\Shrinkr\Http\Controllers\RedirectController::class,
```

### Creating Custom Controller

**Step 1:** Create your controller:

```php
<?php

namespace App\Http\Controllers;

use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use Illuminate\Http\Request;

class CustomRedirectController extends Controller
{
    public function __invoke(Request $request, string $slug)
    {
        // Custom logic before redirect
        \Log::info("Redirecting: {$slug}");

        $url = Shrinkr::resolve($slug);

        if (!$url) {
            abort(404, 'Short URL not found');
        }

        // Custom logic after resolving

        return redirect($url);
    }
}
```

**Step 2:** Configure the controller:

```php
'controller' => \App\Http\Controllers\CustomRedirectController::class,
```

## Configuration Examples

### Example 1: Branded Short Domain

```php
return [
    'prefix' => '',
    'domain' => 'go.mybrand.com',
    'middleware' => ['throttle:100,1'],
    'route-name' => 'brand.short',
];
```

**Result:** `https://go.mybrand.com/abc123`

### Example 2: Authenticated Service

```php
return [
    'prefix' => 'link',
    'domain' => null,
    'middleware' => ['auth', 'verified', 'throttle:60,1'],
    'route-name' => 'shrnkr.redirect',
];
```

**Result:** Only authenticated users can access shortened URLs

### Example 3: High-Traffic Setup

```php
return [
    'prefix' => 's',
    'domain' => null,
    'middleware' => ['throttle:1000,1', 'cache.headers:public;max_age=3600'],
    'route-name' => 'shrnkr.redirect',
];
```

**Result:** High rate limit with response caching

## Route Conflicts

If Shrinkr routes conflict with your application routes:

1. **Change the prefix:**

```php
'prefix' => 'shrink',
```

2. **Use a subdomain:**

```php
'domain' => 'short.yourdomain.com',
'prefix' => '',
```

3. **Change route priority** by registering routes in a specific order in your `RouteServiceProvider`

## Testing Routes

Test your routing configuration:

```bash
php artisan route:list --name=shrnkr
```

Access a test URL:

```bash
curl -I https://yourdomain.com/s/test-slug
```

## Next Steps

- [Model Customization](04-models.md) - Extend Shrinkr models
- [Usage Guide](../03-usage/README.md) - Learn how to use Shrinkr
- [Middleware](../04-features/05-middleware.md) - Advanced middleware patterns

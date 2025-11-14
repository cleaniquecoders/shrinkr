# Middleware

Shrinkr uses middleware to control access to shortened URLs and provide additional security and functionality.

## Default Middleware

By default, Shrinkr applies rate limiting middleware:

```php
// config/shrinkr.php
'middleware' => ['throttle:60,1'],
```

This allows 60 requests per minute per IP address.

## Configuring Middleware

### Rate Limiting

Adjust rate limits in the configuration:

```php
// 100 requests per minute
'middleware' => ['throttle:100,1'],

// 1000 requests per hour
'middleware' => ['throttle:1000,60'],

// 10000 requests per day
'middleware' => ['throttle:10000,1440'],
```

### Authentication

Require authentication to access shortened URLs:

```php
'middleware' => ['auth', 'throttle:60,1'],
```

**Note:** This protects the redirect route. Users must be logged in to access any shortened URL.

### Multiple Middleware

Combine multiple middleware:

```php
'middleware' => [
    'auth',
    'verified',
    'throttle:100,1',
    'custom-middleware',
],
```

## Custom Middleware

### Create Custom Middleware

```bash
php artisan make:middleware TrackShrinkrAccess
```

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackShrinkrAccess
{
    public function handle(Request $request, Closure $next)
    {
        $slug = $request->route('slug');

        Log::info('Shrinkr URL accessed', [
            'slug' => $slug,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);

        return $next($request);
    }
}
```

### Register Middleware

In `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ...
    'track-shrinkr' => \App\Http\Middleware\TrackShrinkrAccess::class,
];
```

### Apply Middleware

```php
// config/shrinkr.php
'middleware' => ['throttle:60,1', 'track-shrinkr'],
```

## Common Middleware Patterns

### Geographic Restrictions

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictByCountry
{
    public function handle(Request $request, Closure $next)
    {
        $country = geoip($request->ip())->country;
        $allowedCountries = ['US', 'CA', 'GB'];

        if (!in_array($country, $allowedCountries)) {
            abort(403, 'Access denied from your location.');
        }

        return $next($request);
    }
}
```

### Time-based Access

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BusinessHoursOnly
{
    public function handle(Request $request, Closure $next)
    {
        $hour = now()->hour;

        // Only allow access during business hours (9 AM - 5 PM)
        if ($hour < 9 || $hour >= 17) {
            return response()->view('errors.outside-business-hours', [], 403);
        }

        return $next($request);
    }
}
```

### Bot Detection

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class BlockBots
{
    public function handle(Request $request, Closure $next)
    {
        $agent = new Agent();

        if ($agent->isRobot()) {
            abort(403, 'Bot access not allowed');
        }

        return $next($request);
    }
}
```

### Custom Rate Limiting

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CustomRateLimit
{
    public function handle(Request $request, Closure $next)
    {
        $key = 'url-' . $request->route('slug') . '-' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many requests',
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($key, 60); // 60 seconds

        return $next($request);
    }
}
```

### Password Protection

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use CleaniqueCoders\Shrinkr\Models\Url;

class PasswordProtectedUrl
{
    public function handle(Request $request, Closure $next)
    {
        $slug = $request->route('slug');
        $url = Url::where('slug', $slug)->first();

        // Check if URL requires password
        if ($url && $url->password) {
            $sessionKey = "url_password_{$url->id}";

            // Check if already authenticated
            if (session($sessionKey) !== true) {
                return redirect()->route('url.password', $slug);
            }
        }

        return $next($request);
    }
}
```

## Conditional Middleware

Apply middleware based on conditions:

```php
// In a custom service provider
use Illuminate\Support\Facades\Route;

public function boot()
{
    Route::middleware(['web'])
        ->group(function () {
            if (config('shrinkr.require_auth')) {
                Route::middleware(['auth'])->group(base_path('routes/shrinkr.php'));
            } else {
                Route::group(base_path('routes/shrinkr.php'));
            }
        });
}
```

## Testing Middleware

```php
use CleaniqueCoders\Shrinkr\Models\Url;

test('rate limiting prevents too many requests', function () {
    $url = Url::factory()->create(['slug' => 'test']);

    // Make 60 requests (within limit)
    for ($i = 0; $i < 60; $i++) {
        $response = $this->get(route('shrnkr.redirect', 'test'));
        $response->assertStatus(302);
    }

    // 61st request should be rate limited
    $response = $this->get(route('shrnkr.redirect', 'test'));
    $response->assertStatus(429);
});

test('authenticated middleware requires login', function () {
    config(['shrinkr.middleware' => ['auth', 'throttle:60,1']]);

    $url = Url::factory()->create(['slug' => 'auth-test']);

    $response = $this->get(route('shrnkr.redirect', 'auth-test'));
    $response->assertRedirect(route('login'));
});
```

## Next Steps

- [Configuration](../02-configuration/03-routing.md) - Configure middleware
- [Security Best Practices](../06-development/03-security.md) - Secure your URLs
- [Commands](06-commands.md) - Maintenance commands

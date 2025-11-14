# Events

Shrinkr dispatches events at key moments in the URL lifecycle, allowing you to hook into and respond to these actions.

## Available Events

### UrlAccessed

Dispatched whenever a shortened URL is accessed.

**Event Class:**

```php
namespace CleaniqueCoders\Shrinkr\Events;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;

class UrlAccessed
{
    public function __construct(
        public Url $url,
        public Request $request
    ) {}
}
```

**When Triggered:**

- User visits a shortened URL
- The `resolve()` method is called
- Before redirect happens

### UrlExpired

Dispatched when a URL expires.

**Event Class:**

```php
namespace CleaniqueCoders\Shrinkr\Events;

use CleaniqueCoders\Shrinkr\Models\Url;

class UrlExpired
{
    public function __construct(public Url $url) {}
}
```

**When Triggered:**

- `shrinkr:check-expiry` command runs
- URL with expired date is detected

## Listening to Events

### Create a Listener

```bash
php artisan make:listener LogUrlAccess
```

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use Illuminate\Support\Facades\Log;

class LogUrlAccess
{
    public function handle(UrlAccessed $event): void
    {
        $url = $event->url;
        $request = $event->request;

        Log::info('URL accessed', [
            'slug' => $url->slug,
            'original_url' => $url->original_url,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
        ]);
    }
}
```

### Register the Listener

In `app/Providers/EventServiceProvider.php`:

```php
use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use App\Listeners\LogUrlAccess;
use App\Listeners\NotifyUrlExpired;

protected $listen = [
    UrlAccessed::class => [
        LogUrlAccess::class,
    ],
    UrlExpired::class => [
        NotifyUrlExpired::class,
    ],
];
```

## Common Use Cases

### Send Analytics to External Service

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use Illuminate\Support\Facades\Http;

class SendToAnalytics
{
    public function handle(UrlAccessed $event): void
    {
        Http::post('https://analytics.example.com/track', [
            'url_id' => $event->url->id,
            'slug' => $event->url->slug,
            'ip' => $event->request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

### Notify User of URL Access

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use App\Notifications\UrlAccessedNotification;

class NotifyUrlOwner
{
    public function handle(UrlAccessed $event): void
    {
        $url = $event->url;

        if ($url->user && $url->notify_on_access) {
            $url->user->notify(new UrlAccessedNotification($url, $event->request));
        }
    }
}
```

### Handle Expired URLs

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use App\Notifications\UrlExpiredNotification;
use Illuminate\Support\Facades\Log;

class NotifyUrlExpired
{
    public function handle(UrlExpired $event): void
    {
        $url = $event->url;

        Log::warning("URL expired: {$url->slug}", [
            'url_id' => $url->id,
            'expired_at' => $url->expires_at,
        ]);

        // Notify the owner
        if ($url->user) {
            $url->user->notify(new UrlExpiredNotification($url));
        }

        // Clean up old logs
        $url->logs()->where('created_at', '<', now()->subDays(90))->delete();
    }
}
```

### Rate Limit Detection

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DetectSuspiciousActivity
{
    public function handle(UrlAccessed $event): void
    {
        $ip = $event->request->ip();
        $url = $event->url;

        $key = "url_access:{$url->id}:{$ip}";
        $count = Cache::increment($key);

        if ($count === 1) {
            Cache::put($key, 1, now()->addMinutes(5));
        }

        // Flag suspicious activity (>10 requests in 5 minutes)
        if ($count > 10) {
            Log::warning('Suspicious activity detected', [
                'url_id' => $url->id,
                'ip' => $ip,
                'count' => $count,
            ]);

            // Optionally block or notify
        }
    }
}
```

## Queue Listeners

For better performance, queue your listeners:

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendToAnalytics implements ShouldQueue
{
    public function handle(UrlAccessed $event): void
    {
        // This will run asynchronously in a queue
        Http::post('https://analytics.example.com/track', [
            'url_id' => $event->url->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

## Broadcasting Events

Broadcast events for real-time updates:

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use App\Events\UrlClickedRealtime;

class BroadcastUrlAccess
{
    public function handle(UrlAccessed $event): void
    {
        broadcast(new UrlClickedRealtime($event->url));
    }
}
```

## Testing Event Listeners

```php
use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use Illuminate\Support\Facades\Event;

test('dispatches url accessed event', function () {
    Event::fake([UrlAccessed::class]);

    $url = Url::factory()->create();
    Shrinkr::resolve($url->slug);

    Event::assertDispatched(UrlAccessed::class);
});

test('dispatches url expired event', function () {
    Event::fake([UrlExpired::class]);

    $url = Url::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('shrinkr:check-expiry');

    Event::assertDispatched(UrlExpired::class);
});
```

## Next Steps

- [Analytics](02-analytics.md) - Track event data
- [Commands](06-commands.md) - Commands that trigger events
- [API Reference](../05-api-reference/04-events.md) - Event class reference

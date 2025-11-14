# Events

Shrinkr dispatches events at key moments in the URL lifecycle, allowing you to hook into these actions and respond accordingly.

## Available Events

### UrlAccessed

Dispatched when a shortened URL is accessed via the redirect route.

#### Namespace

```php
use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
```

#### Properties

```php
public Url $url;          // The accessed URL model
public Request $request;  // The HTTP request instance (if applicable)
```

#### When Dispatched

- User visits a shortened URL
- The redirect controller handles the request
- Before the actual redirect occurs

#### Example Listener

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use Illuminate\Support\Facades\Log;

class LogUrlAccess
{
    public function handle(UrlAccessed $event): void
    {
        Log::info('URL accessed', [
            'url_id' => $event->url->id,
            'slug' => $event->url->shortened_url,
            'original' => $event->url->original_url,
            'ip' => $event->request->ip(),
            'user_agent' => $event->request->userAgent(),
        ]);
    }
}
```

#### Registration

In `EventServiceProvider`:

```php
use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use App\Listeners\LogUrlAccess;

protected $listen = [
    UrlAccessed::class => [
        LogUrlAccess::class,
    ],
];
```

### UrlExpired

Dispatched when a URL is marked as expired.

#### Namespace

```php
use CleaniqueCoders\Shrinkr\Events\UrlExpired;
```

#### Properties

```php
public Url $url;  // The expired URL model
```

#### When Dispatched

- `shrinkr:check-expiry` command runs and finds expired URLs
- Health check fails and marks URL as expired
- Manual call to `$url->markAsExpired()`

#### Example Listener

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use App\Notifications\UrlExpiredNotification;

class NotifyUrlExpired
{
    public function handle(UrlExpired $event): void
    {
        $url = $event->url;

        // Notify the URL owner
        if ($url->user) {
            $url->user->notify(new UrlExpiredNotification($url));
        }

        // Log the expiration
        \Log::warning('URL expired', [
            'url_id' => $url->id,
            'slug' => $url->shortened_url,
            'expired_at' => $url->expires_at,
        ]);

        // Clean up old logs
        $url->logs()->where('created_at', '<', now()->subDays(90))->delete();
    }
}
```

#### Registration

```php
use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use App\Listeners\NotifyUrlExpired;

protected $listen = [
    UrlExpired::class => [
        NotifyUrlExpired::class,
    ],
];
```

## Event Listener Patterns

### Queued Listeners

Process events asynchronously for better performance:

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendToAnalytics implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UrlAccessed $event): void
    {
        // This runs in a queue worker
        Http::post('https://analytics.example.com/track', [
            'url_id' => $event->url->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

### Multiple Listeners

Register multiple listeners for the same event:

```php
protected $listen = [
    UrlAccessed::class => [
        LogUrlAccess::class,
        SendToAnalytics::class,
        UpdateStatistics::class,
        NotifyUrlOwner::class,
    ],
];
```

### Conditional Listeners

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;

class NotifyHighValueClicks
{
    public function handle(UrlAccessed $event): void
    {
        // Only notify for specific URLs
        if ($event->url->custom_slug && str_starts_with($event->url->custom_slug, 'vip-')) {
            // Send notification
            event(new HighValueUrlClicked($event->url));
        }
    }
}
```

## Event Subscribers

Group related event listeners:

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use Illuminate\Events\Dispatcher;

class UrlEventSubscriber
{
    public function handleUrlAccessed(UrlAccessed $event): void
    {
        // Handle URL access
    }

    public function handleUrlExpired(UrlExpired $event): void
    {
        // Handle URL expiration
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            UrlAccessed::class => 'handleUrlAccessed',
            UrlExpired::class => 'handleUrlExpired',
        ];
    }
}
```

Register in `EventServiceProvider`:

```php
protected $subscribe = [
    \App\Listeners\UrlEventSubscriber::class,
];
```

## Testing Events

### Assert Events Dispatched

```php
use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use Illuminate\Support\Facades\Event;

test('dispatches url accessed event', function () {
    Event::fake([UrlAccessed::class]);

    $url = Url::factory()->create();

    $this->get(route('shrnkr.redirect', $url->shortened_url));

    Event::assertDispatched(UrlAccessed::class, function ($event) use ($url) {
        return $event->url->id === $url->id;
    });
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

### Test Listeners

```php
use App\Listeners\LogUrlAccess;
use CleaniqueCoders\Shrinkr\Events\UrlAccessed;

test('listener logs url access', function () {
    Log::spy();

    $url = Url::factory()->create();
    $request = Request::create('/');

    $event = new UrlAccessed($url, $request);
    $listener = new LogUrlAccess();

    $listener->handle($event);

    Log::shouldHaveReceived('info')
        ->once()
        ->with('URL accessed', Mockery::type('array'));
});
```

## Broadcasting Events

Broadcast events for real-time updates:

```php
namespace App\Events;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UrlClickedRealtime implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Url $url,
        public int $totalClicks
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('urls.' . $this->url->id);
    }

    public function broadcastAs(): string
    {
        return 'url.clicked';
    }
}
```

Listen in a Shrinkr event listener:

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use App\Events\UrlClickedRealtime;

class BroadcastUrlClick
{
    public function handle(UrlAccessed $event): void
    {
        $totalClicks = $event->url->logs()->count();

        broadcast(new UrlClickedRealtime($event->url, $totalClicks));
    }
}
```

## Custom Events

Create your own events that integrate with Shrinkr:

```php
namespace App\Events;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Foundation\Events\Dispatchable;

class UrlReachedClickThreshold
{
    use Dispatchable;

    public function __construct(
        public Url $url,
        public int $threshold
    ) {}
}
```

Dispatch in a listener:

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use App\Events\UrlReachedClickThreshold;

class CheckClickThreshold
{
    public function handle(UrlAccessed $event): void
    {
        $clicks = $event->url->logs()->count();

        if ($clicks % 1000 === 0) {
            event(new UrlReachedClickThreshold($event->url, $clicks));
        }
    }
}
```

## Next Steps

- [Features: Events](../04-features/03-events.md) - Event usage patterns
- [Analytics](../04-features/02-analytics.md) - Track event data
- [Commands](../04-features/06-commands.md) - Commands that trigger events

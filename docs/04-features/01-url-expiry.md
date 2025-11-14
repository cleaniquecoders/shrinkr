# URL Expiry

Shrinkr allows you to set expiration times for shortened URLs, perfect for temporary links, campaigns, and time-limited offers.

## Setting Expiry Duration

### When Creating URLs

Specify expiry duration in minutes when creating a short URL:

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

// Expires in 60 minutes
$url = Shrinkr::shorten('https://example.com', auth()->id(), [
    'expiry_duration' => 60,
]);

// Expires in 24 hours
$url = Shrinkr::shorten('https://example.com', auth()->id(), [
    'expiry_duration' => 1440,
]);

// Expires in 7 days
$url = Shrinkr::shorten('https://example.com', auth()->id(), [
    'expiry_duration' => 10080,
]);
```

### Common Durations

| Duration | Minutes | Use Case |
|----------|---------|----------|
| 1 hour | 60 | Temporary downloads |
| 24 hours | 1440 | Daily deals |
| 7 days | 10080 | Weekly promotions |
| 30 days | 43200 | Monthly campaigns |
| 90 days | 129600 | Quarterly offers |

## Checking Expiry Status

### Check if URL is Expired

```php
use CleaniqueCoders\Shrinkr\Models\Url;

$url = Url::find(1);

if ($url->expires_at && $url->expires_at->isPast()) {
    echo "URL has expired";
} else {
    echo "URL is still active";
}
```

### Get Time Remaining

```php
$url = Url::find(1);

if ($url->expires_at) {
    echo $url->expires_at->diffForHumans(); // "2 days from now"
    echo $url->expires_at->diffInHours() . " hours remaining";
}
```

## Automatic Expiry Checking

### Check Expiry Command

Run the command manually to mark expired URLs:

```bash
php artisan shrinkr:check-expiry
```

This command:

1. Finds all URLs with expiry dates in the past
2. Marks them as expired
3. Dispatches the `UrlExpired` event for each

### Scheduling Expiry Checks

Add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Check every hour
    $schedule->command('shrinkr:check-expiry')->hourly();

    // Or check every 15 minutes
    $schedule->command('shrinkr:check-expiry')->everyFifteenMinutes();
}
```

## UrlExpired Event

When a URL expires, the `UrlExpired` event is dispatched.

### Event Structure

```php
namespace CleaniqueCoders\Shrinkr\Events;

use CleaniqueCoders\Shrinkr\Models\Url;

class UrlExpired
{
    public function __construct(public Url $url) {}
}
```

### Listening to the Event

Create a listener:

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HandleUrlExpired
{
    public function handle(UrlExpired $event): void
    {
        $url = $event->url;

        Log::info("URL expired: {$url->slug}");

        // Notify the URL owner
        if ($url->user) {
            Mail::to($url->user)->send(new UrlExpiredNotification($url));
        }

        // Clean up related data
        $url->logs()->where('created_at', '<', now()->subDays(30))->delete();
    }
}
```

Register in `EventServiceProvider`:

```php
use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use App\Listeners\HandleUrlExpired;

protected $listen = [
    UrlExpired::class => [
        HandleUrlExpired::class,
    ],
];
```

## Handling Expired URL Access

### In Controller

```php
public function redirect($slug)
{
    $url = Url::where('slug', $slug)->first();

    if (!$url) {
        abort(404);
    }

    if ($url->expires_at && $url->expires_at->isPast()) {
        return view('errors.url-expired', [
            'url' => $url,
            'message' => 'This short URL has expired.',
        ]);
    }

    return redirect($url->original_url);
}
```

### Custom Error View

```blade
{{-- resources/views/errors/url-expired.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="error-page">
    <h1>Link Expired</h1>
    <p>{{ $message }}</p>

    @if($url->user)
        <p>This link was created on {{ $url->created_at->format('M d, Y') }}
           and expired on {{ $url->expires_at->format('M d, Y') }}.</p>
    @endif

    <a href="{{ url('/') }}">Go Home</a>
</div>
@endsection
```

## Extending Expiry

### Extend Before Expiration

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

$url = Url::find(1);

// Add 7 more days
$updatedUrl = Shrinkr::update($url, [
    'expiry_duration' => $url->expires_at->diffInMinutes(now()) + 10080,
]);
```

### Reactivate Expired URL

```php
$expiredUrl = Url::where('slug', 'expired')->first();

// Set new expiry 30 days from now
Shrinkr::update($expiredUrl, [
    'expiry_duration' => 43200, // 30 days
]);
```

### Remove Expiry (Make Permanent)

```php
$url->update(['expires_at' => null]);
```

## Expiry Notifications

### Email Notification Before Expiry

```php
// app/Console/Commands/NotifyExpiringUrls.php
namespace App\Console\Commands;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Console\Command;

class NotifyExpiringUrls extends Command
{
    protected $signature = 'urls:notify-expiring';

    public function handle(): int
    {
        // URLs expiring in the next 24 hours
        $expiringUrls = Url::whereBetween('expires_at', [
            now(),
            now()->addDay(),
        ])->with('user')->get();

        foreach ($expiringUrls as $url) {
            if ($url->user) {
                Mail::to($url->user)->send(new UrlExpiringNotification($url));
            }
        }

        $this->info("Notified {$expiringUrls->count()} users");

        return self::SUCCESS;
    }
}
```

Schedule:

```php
$schedule->command('urls:notify-expiring')->daily();
```

## Bulk Expiry Management

### Set Expiry for Multiple URLs

```php
// Expire all URLs in a campaign
Url::where('slug', 'like', 'campaign-%')
    ->update(['expires_at' => now()->addDays(30)]);

// Extend all expiring URLs
Url::whereBetween('expires_at', [now(), now()->addDays(7)])
    ->get()
    ->each(function ($url) {
        $url->update(['expires_at' => $url->expires_at->addDays(30)]);
    });
```

### Delete Expired URLs

```php
Url::whereNotNull('expires_at')
    ->where('expires_at', '<=', now()->subDays(30))
    ->delete();
```

## Expiry Analytics

Track expiry statistics:

```php
class ExpiryAnalytics
{
    public function getStatistics(): array
    {
        return [
            'total_with_expiry' => Url::whereNotNull('expires_at')->count(),
            'expired' => Url::whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->count(),
            'expiring_soon' => Url::whereBetween('expires_at', [
                now(),
                now()->addDays(7),
            ])->count(),
            'permanent' => Url::whereNull('expires_at')->count(),
        ];
    }
}
```

## Next Steps

- [Analytics](02-analytics.md) - Track URL performance
- [Events](03-events.md) - Handle expiry events
- [Commands](06-commands.md) - Automate expiry management

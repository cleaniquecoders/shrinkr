# Health Monitoring

Shrinkr includes health monitoring to ensure that original URLs are still reachable and functional.

## Overview

Health monitoring validates that shortened URLs point to active, reachable destinations. When a URL becomes unreachable, Shrinkr can automatically mark it as expired and notify you.

## Checking URL Health

### Manual Health Check

Use the `CheckUrlHealthAction` to check a specific URL:

```php
use CleaniqueCoders\Shrinkr\Actions\CheckUrlHealthAction;
use CleaniqueCoders\Shrinkr\Models\Url;

$url = Url::find(1);
$action = new CheckUrlHealthAction();

$isHealthy = $action->execute($url);

if ($isHealthy) {
    echo "URL is active and reachable";
} else {
    echo "URL is inactive or unreachable";
}
```

### What is Checked

The health check:

1. Sends an HTTP request to the original URL
2. Checks for successful response (2xx or 3xx status codes)
3. Marks URL as active or expired based on response
4. Dispatches `UrlExpired` event if URL becomes inactive

## Health Check Command

Run health checks for all URLs:

```bash
php artisan shrinkr:check-health
```

**Output:**

```
Checking health for 150 URLs...
URL abc123 is active.
URL xyz456 is expired (unreachable).
URL def789 is active.
...
Health check completed: 140 active, 10 expired.
```

### Command Options

The command checks all URLs by default. To check specific URLs:

```php
// In your code
Artisan::call('shrinkr:check-health');
```

## Scheduling Health Checks

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Check health hourly
    $schedule->command('shrinkr:check-health')->hourly();

    // Or check daily at 2 AM
    $schedule->command('shrinkr:check-health')->dailyAt('02:00');

    // Or check weekly
    $schedule->command('shrinkr:check-health')->weekly();
}
```

## Response to Failed Health Checks

### Listen to UrlExpired Event

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use App\Notifications\UrlBecameInactiveNotification;

class HandleInactiveUrl
{
    public function handle(UrlExpired $event): void
    {
        $url = $event->url;

        // Notify the URL owner
        if ($url->user) {
            $url->user->notify(new UrlBecameInactiveNotification($url));
        }

        // Log for review
        \Log::warning("URL became inactive", [
            'url_id' => $url->id,
            'slug' => $url->slug,
            'original_url' => $url->original_url,
        ]);
    }
}
```

## Custom Health Check Logic

Extend the health check action for custom validation:

```php
namespace App\Actions;

use CleaniqueCoders\Shrinkr\Actions\CheckUrlHealthAction as BaseAction;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Support\Facades\Http;

class CustomHealthCheckAction extends BaseAction
{
    public function execute(Url $url): bool
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'Shrinkr-Health-Check/1.0'])
                ->get($url->original_url);

            // Custom logic: Check content as well
            $isHealthy = $response->successful() &&
                         !str_contains($response->body(), 'Page Not Found');

            if ($isHealthy) {
                $url->update(['is_active' => true]);
            } else {
                $url->update(['is_active' => false, 'expires_at' => now()]);
                event(new \CleaniqueCoders\Shrinkr\Events\UrlExpired($url));
            }

            return $isHealthy;

        } catch (\Exception $e) {
            $url->update(['is_active' => false, 'expires_at' => now()]);
            event(new \CleaniqueCoders\Shrinkr\Events\UrlExpired($url));
            return false;
        }
    }
}
```

## Health Check Reports

Generate health reports for monitoring:

```php
namespace App\Services;

use CleaniqueCoders\Shrinkr\Models\Url;

class HealthReportService
{
    public function generateReport(): array
    {
        $total = Url::count();
        $active = Url::whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->count();
        $expired = $total - $active;

        return [
            'total_urls' => $total,
            'active_urls' => $active,
            'expired_urls' => $expired,
            'health_percentage' => $total > 0 ? ($active / $total) * 100 : 0,
            'checked_at' => now(),
        ];
    }

    public function getUnhealthyUrls()
    {
        return Url::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with('user')
            ->get();
    }
}
```

## Dashboard Integration

Display health status in your admin dashboard:

```php
// In your controller
public function dashboard()
{
    $healthService = new HealthReportService();
    $report = $healthService->generateReport();
    $unhealthyUrls = $healthService->getUnhealthyUrls();

    return view('admin.dashboard', compact('report', 'unhealthyUrls'));
}
```

```blade
{{-- resources/views/admin/dashboard.blade.php --}}
<div class="health-status">
    <h2>URL Health Status</h2>

    <div class="stats">
        <div class="stat">
            <h3>Total URLs</h3>
            <p>{{ $report['total_urls'] }}</p>
        </div>
        <div class="stat">
            <h3>Active</h3>
            <p class="text-success">{{ $report['active_urls'] }}</p>
        </div>
        <div class="stat">
            <h3>Expired</h3>
            <p class="text-danger">{{ $report['expired_urls'] }}</p>
        </div>
        <div class="stat">
            <h3>Health %</h3>
            <p>{{ number_format($report['health_percentage'], 1) }}%</p>
        </div>
    </div>

    @if($unhealthyUrls->isNotEmpty())
        <h3>Unhealthy URLs</h3>
        <table>
            <thead>
                <tr>
                    <th>Slug</th>
                    <th>Original URL</th>
                    <th>Owner</th>
                    <th>Expired At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unhealthyUrls as $url)
                <tr>
                    <td>{{ $url->slug }}</td>
                    <td>{{ $url->original_url }}</td>
                    <td>{{ $url->user->name ?? 'N/A' }}</td>
                    <td>{{ $url->expires_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
```

## Batch Health Checks

Check health for specific URL groups:

```php
// Check URLs by user
$user = User::find(1);
$user->urls->each(function ($url) {
    $action = new CheckUrlHealthAction();
    $action->execute($url);
});

// Check URLs expiring soon
Url::whereBetween('expires_at', [now(), now()->addDays(7)])
    ->get()
    ->each(function ($url) {
        (new CheckUrlHealthAction())->execute($url);
    });
```

## Next Steps

- [Events](03-events.md) - Handle health check events
- [Commands](06-commands.md) - Automated health checks
- [Analytics](02-analytics.md) - Track URL performance

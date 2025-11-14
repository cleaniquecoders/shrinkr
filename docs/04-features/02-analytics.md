# Analytics

Shrinkr provides comprehensive analytics tracking for all shortened URLs, capturing visitor information, referrers, devices, and more.

## What is Tracked

For each URL access, Shrinkr tracks:

- **IP Address** - Visitor's IP address
- **Browser** - Browser name and version (Chrome, Firefox, Safari, etc.)
- **Platform** - Operating system (Windows, macOS, Linux, etc.)
- **Device** - Device type (desktop, mobile, tablet)
- **Referrer** - Source website where the click originated
- **Headers** - Request headers (JSON)
- **Query Parameters** - URL query parameters (JSON)
- **Timestamp** - When the URL was accessed

## Viewing Analytics

### Get Logs for a URL

```php
use CleaniqueCoders\Shrinkr\Models\Url;

$url = Url::find(1);
$logs = $url->logs()->latest()->get();

foreach ($logs as $log) {
    echo "IP: {$log->ip}\n";
    echo "Browser: {$log->browser}\n";
    echo "Platform: {$log->platform}\n";
    echo "Device: {$log->device}\n";
    echo "Referrer: {$log->referrer}\n";
    echo "Visited: {$log->created_at}\n";
}
```

### Get Analytics Summary

```php
$url = Url::with(['logs' => function($query) {
    $query->latest()->take(100);
}])->find(1);

$analytics = [
    'total_clicks' => $url->logs()->count(),
    'unique_ips' => $url->logs()->distinct('ip')->count(),
    'clicks_today' => $url->logs()->whereDate('created_at', today())->count(),
    'clicks_this_week' => $url->logs()->whereBetween('created_at', [
        now()->startOfWeek(),
        now()->endOfWeek(),
    ])->count(),
    'clicks_this_month' => $url->logs()->whereMonth('created_at', now()->month)->count(),
];
```

### Browser Statistics

```php
$browserStats = $url->logs()
    ->selectRaw('browser, COUNT(*) as count')
    ->groupBy('browser')
    ->orderBy('count', 'desc')
    ->get();

foreach ($browserStats as $stat) {
    echo "{$stat->browser}: {$stat->count} clicks\n";
}
```

### Platform Statistics

```php
$platformStats = $url->logs()
    ->selectRaw('platform, COUNT(*) as count')
    ->groupBy('platform')
    ->orderBy('count', 'desc')
    ->get();
```

### Top Referrers

```php
$topReferrers = $url->logs()
    ->whereNotNull('referrer')
    ->selectRaw('referrer, COUNT(*) as count')
    ->groupBy('referrer')
    ->orderBy('count', 'desc')
    ->take(10)
    ->get();
```

### Device Statistics

```php
$deviceStats = $url->logs()
    ->selectRaw('device, COUNT(*) as count')
    ->groupBy('device')
    ->get();

// Result: ['desktop' => 150, 'mobile' => 75, 'tablet' => 25]
```

## Building an Analytics Dashboard

### Controller

```php
namespace App\Http\Controllers;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function show(Url $url)
    {
        $this->authorize('view', $url);

        $analytics = [
            'total_clicks' => $url->logs()->count(),
            'unique_visitors' => $url->logs()->distinct('ip')->count(),
            'clicks_today' => $url->logs()->whereDate('created_at', today())->count(),
            'clicks_7days' => $url->logs()->where('created_at', '>=', now()->subDays(7))->count(),
            'clicks_30days' => $url->logs()->where('created_at', '>=', now()->subDays(30))->count(),

            'browsers' => $url->logs()
                ->selectRaw('browser, COUNT(*) as count')
                ->groupBy('browser')
                ->orderBy('count', 'desc')
                ->get(),

            'platforms' => $url->logs()
                ->selectRaw('platform, COUNT(*) as count')
                ->groupBy('platform')
                ->get(),

            'devices' => $url->logs()
                ->selectRaw('device, COUNT(*) as count')
                ->groupBy('device')
                ->get(),

            'top_referrers' => $url->logs()
                ->whereNotNull('referrer')
                ->selectRaw('referrer, COUNT(*) as count')
                ->groupBy('referrer')
                ->orderBy('count', 'desc')
                ->take(10)
                ->get(),

            'clicks_by_date' => $url->logs()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->take(30)
                ->get(),
        ];

        return view('analytics.show', compact('url', 'analytics'));
    }
}
```

### Blade View

```blade
{{-- resources/views/analytics/show.blade.php --}}
<div class="analytics">
    <h1>Analytics for {{ $url->slug }}</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Clicks</h3>
            <p class="stat-number">{{ number_format($analytics['total_clicks']) }}</p>
        </div>

        <div class="stat-card">
            <h3>Unique Visitors</h3>
            <p class="stat-number">{{ number_format($analytics['unique_visitors']) }}</p>
        </div>

        <div class="stat-card">
            <h3>Today</h3>
            <p class="stat-number">{{ number_format($analytics['clicks_today']) }}</p>
        </div>

        <div class="stat-card">
            <h3>Last 7 Days</h3>
            <p class="stat-number">{{ number_format($analytics['clicks_7days']) }}</p>
        </div>
    </div>

    <div class="charts">
        <div class="chart">
            <h3>Browsers</h3>
            @foreach($analytics['browsers'] as $browser)
                <div class="bar">
                    <span>{{ $browser->browser }}</span>
                    <span>{{ $browser->count }}</span>
                </div>
            @endforeach
        </div>

        <div class="chart">
            <h3>Top Referrers</h3>
            @foreach($analytics['top_referrers'] as $referrer)
                <div class="bar">
                    <span>{{ $referrer->referrer }}</span>
                    <span>{{ $referrer->count }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
```

## Exporting Analytics

### Export to CSV

```php
public function exportCsv(Url $url)
{
    $logs = $url->logs()->get();

    $csv = "Date,IP,Browser,Platform,Device,Referrer\n";

    foreach ($logs as $log) {
        $csv .= sprintf(
            '"%s","%s","%s","%s","%s","%s"' . "\n",
            $log->created_at->format('Y-m-d H:i:s'),
            $log->ip,
            $log->browser,
            $log->platform,
            $log->device,
            $log->referrer ?? 'Direct'
        );
    }

    return response($csv)
        ->header('Content-Type', 'text/csv')
        ->header('Content-Disposition', "attachment; filename=analytics-{$url->slug}.csv");
}
```

## Real-time Analytics

For real-time updates, use Laravel Echo with broadcasting:

```php
// In your listener
use App\Events\UrlClicked;

class LogUrlAccess
{
    public function handle(UrlAccessed $event)
    {
        // ... save to database

        // Broadcast real-time update
        broadcast(new UrlClicked($event->url));
    }
}
```

## Next Steps

- [Events](03-events.md) - React to analytics events
- [Logging Configuration](../02-configuration/02-logging.md) - Configure analytics storage
- [API Reference](../05-api-reference/03-models.md) - RedirectLog model

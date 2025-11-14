# Working with Users

Learn how to manage URLs on a per-user basis and implement user-specific features.

## User Association

All shortened URLs are associated with a user ID:

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

$shortUrl = Shrinkr::shorten('https://example.com', auth()->id());
```

## Retrieving User's URLs

### Get All URLs for a User

```php
use CleaniqueCoders\Shrinkr\Models\Url;

$userUrls = Url::where('user_id', auth()->id())->get();
```

### With Pagination

```php
$userUrls = Url::where('user_id', auth()->id())
    ->latest()
    ->paginate(20);
```

### With Filters

```php
// Active URLs only
$activeUrls = Url::where('user_id', auth()->id())
    ->where(function ($query) {
        $query->whereNull('expires_at')
            ->orWhere('expires_at', '>', now());
    })
    ->get();

// Expired URLs
$expiredUrls = Url::where('user_id', auth()->id())
    ->whereNotNull('expires_at')
    ->where('expires_at', '<=', now())
    ->get();
```

## User Relationship

Add a relationship to your User model:

```php
<?php

namespace App\Models;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public function urls()
    {
        return $this->hasMany(Url::class);
    }

    public function activeUrls()
    {
        return $this->hasMany(Url::class)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function expiredUrls()
    {
        return $this->hasMany(Url::class)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}
```

Usage:

```php
$user = auth()->user();

// Get all URLs
$allUrls = $user->urls;

// Get active URLs
$activeUrls = $user->activeUrls;

// Count URLs
$urlCount = $user->urls()->count();
```

## User Dashboard

### Controller Example

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'total_urls' => $user->urls()->count(),
            'active_urls' => $user->activeUrls()->count(),
            'expired_urls' => $user->expiredUrls()->count(),
            'total_clicks' => $user->urls()->withCount('logs')->get()->sum('logs_count'),
        ];

        $recentUrls = $user->urls()
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard', compact('stats', 'recentUrls'));
    }
}
```

### Blade View Example

```blade
{{-- resources/views/dashboard.blade.php --}}
<div class="dashboard">
    <h1>My URLs</h1>

    <div class="stats">
        <div class="stat">
            <h3>Total URLs</h3>
            <p>{{ $stats['total_urls'] }}</p>
        </div>
        <div class="stat">
            <h3>Active</h3>
            <p>{{ $stats['active_urls'] }}</p>
        </div>
        <div class="stat">
            <h3>Expired</h3>
            <p>{{ $stats['expired_urls'] }}</p>
        </div>
        <div class="stat">
            <h3>Total Clicks</h3>
            <p>{{ $stats['total_clicks'] }}</p>
        </div>
    </div>

    <h2>Recent URLs</h2>
    <table>
        <thead>
            <tr>
                <th>Slug</th>
                <th>Original URL</th>
                <th>Clicks</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentUrls as $url)
            <tr>
                <td>{{ $url->slug }}</td>
                <td>{{ $url->original_url }}</td>
                <td>{{ $url->logs_count ?? 0 }}</td>
                <td>{{ $url->created_at->diffForHumans() }}</td>
                <td>
                    <a href="{{ route('urls.show', $url) }}">View</a>
                    <a href="{{ route('urls.edit', $url) }}">Edit</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

## User Limits

### Implement URL Limits

```php
<?php

namespace App\Services;

use App\Models\User;

class UrlLimitService
{
    public function canCreateUrl(User $user): bool
    {
        $limit = $this->getUrlLimit($user);

        if ($limit === null) {
            return true; // Unlimited
        }

        return $user->urls()->count() < $limit;
    }

    public function getUrlLimit(User $user): ?int
    {
        // Based on subscription plan
        return match($user->plan) {
            'free' => 10,
            'basic' => 100,
            'pro' => 1000,
            'enterprise' => null, // Unlimited
            default => 10,
        };
    }

    public function getRemainingUrls(User $user): ?int
    {
        $limit = $this->getUrlLimit($user);

        if ($limit === null) {
            return null; // Unlimited
        }

        return max(0, $limit - $user->urls()->count());
    }
}
```

### Usage in Controller

```php
use App\Services\UrlLimitService;

class UrlController extends Controller
{
    public function __construct(
        protected UrlLimitService $limitService
    ) {}

    public function store(Request $request)
    {
        if (!$this->limitService->canCreateUrl(auth()->user())) {
            return back()->withErrors([
                'limit' => 'You have reached your URL limit. Please upgrade your plan.',
            ]);
        }

        // Create URL...
    }
}
```

## User Analytics

### Get User Statistics

```php
class UserAnalyticsService
{
    public function getStatistics(User $user): array
    {
        return [
            'total_urls' => $user->urls()->count(),
            'active_urls' => $user->activeUrls()->count(),
            'total_clicks' => $this->getTotalClicks($user),
            'clicks_today' => $this->getClicksToday($user),
            'clicks_this_week' => $this->getClicksThisWeek($user),
            'clicks_this_month' => $this->getClicksThisMonth($user),
            'most_popular_url' => $this->getMostPopularUrl($user),
            'top_referrers' => $this->getTopReferrers($user),
        ];
    }

    protected function getTotalClicks(User $user): int
    {
        return $user->urls()
            ->withCount('logs')
            ->get()
            ->sum('logs_count');
    }

    protected function getClicksToday(User $user): int
    {
        return \DB::table('redirect_logs')
            ->whereIn('url_id', $user->urls()->pluck('id'))
            ->whereDate('created_at', today())
            ->count();
    }

    protected function getMostPopularUrl(User $user)
    {
        return $user->urls()
            ->withCount('logs')
            ->orderBy('logs_count', 'desc')
            ->first();
    }

    protected function getTopReferrers(User $user, int $limit = 10): array
    {
        return \DB::table('redirect_logs')
            ->whereIn('url_id', $user->urls()->pluck('id'))
            ->whereNotNull('referrer')
            ->select('referrer', \DB::raw('COUNT(*) as count'))
            ->groupBy('referrer')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
```

## User API Tokens

For API access, use Laravel Sanctum:

### Generate API Token

```php
// In UserController
public function generateApiToken(Request $request)
{
    $token = $request->user()->createToken('api-token');

    return response()->json([
        'token' => $token->plainTextToken,
    ]);
}
```

### Use Token in API

```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -X POST \
  -d '{"url":"https://example.com"}' \
  https://yourdomain.com/api/shorten
```

## User Export

Allow users to export their URLs:

```php
class UrlExportService
{
    public function exportToCsv(User $user): string
    {
        $urls = $user->urls()->withCount('logs')->get();

        $csv = "Slug,Original URL,Shortened URL,Clicks,Created At,Expires At\n";

        foreach ($urls as $url) {
            $csv .= sprintf(
                '"%s","%s","%s",%d,"%s","%s"' . "\n",
                $url->slug,
                $url->original_url,
                $url->shortened_url,
                $url->logs_count ?? 0,
                $url->created_at->toDateTimeString(),
                $url->expires_at?->toDateTimeString() ?? 'Never'
            );
        }

        return $csv;
    }
}
```

Controller:

```php
public function export()
{
    $service = new UrlExportService();
    $csv = $service->exportToCsv(auth()->user());

    return response($csv)
        ->header('Content-Type', 'text/csv')
        ->header('Content-Disposition', 'attachment; filename="my-urls.csv"');
}
```

## User Permissions

Using Laravel's Gate:

```php
// app/Providers/AuthServiceProvider.php
use Illuminate\Support\Facades\Gate;
use CleaniqueCoders\Shrinkr\Models\Url;

public function boot(): void
{
    Gate::define('view-url', function (User $user, Url $url) {
        return $user->id === $url->user_id;
    });

    Gate::define('update-url', function (User $user, Url $url) {
        return $user->id === $url->user_id;
    });

    Gate::define('delete-url', function (User $user, Url $url) {
        return $user->id === $url->user_id;
    });
}
```

Usage:

```php
if (Gate::allows('view-url', $url)) {
    // User can view this URL
}

if (Gate::denies('update-url', $url)) {
    abort(403);
}
```

## Next Steps

- [Analytics](../04-features/02-analytics.md) - User-specific analytics
- [API Reference](../05-api-reference/README.md) - API documentation
- [Development](../06-development/README.md) - Testing user features

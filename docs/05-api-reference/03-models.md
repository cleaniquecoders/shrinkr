# Models

Shrinkr provides two main Eloquent models for managing URLs and tracking redirects.

## Url Model

The primary model representing shortened URLs.

### Namespace

```php
use CleaniqueCoders\Shrinkr\Models\Url;
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Primary key |
| `uuid` | string | Unique identifier (UUID) |
| `user_id` | int\|null | Associated user ID |
| `original_url` | string | The original long URL |
| `shortened_url` | string | The generated short slug |
| `custom_slug` | string\|null | Custom slug if provided |
| `is_expired` | bool | Whether the URL has expired |
| `expires_at` | Carbon\|null | Expiration timestamp |
| `recheck_at` | Carbon\|null | Next health check timestamp |
| `created_at` | Carbon | Creation timestamp |
| `updated_at` | Carbon | Last update timestamp |

### Fillable Attributes

```php
protected $fillable = [
    'user_id',
    'uuid',
    'original_url',
    'shortened_url',
    'custom_slug',
    'is_expired',
    'expires_at',
    'recheck_at',
];
```

### Casts

```php
protected $casts = [
    'is_expired' => 'boolean',
    'recheck_at' => 'datetime',
    'expires_at' => 'datetime',
];
```

### Relationships

#### user()

Get the user who created the URL.

```php
public function user(): BelongsTo
```

**Example:**

```php
$url = Url::find(1);
$creator = $url->user;

echo $creator->name;
```

#### redirectLogs() / logs()

Get all redirect logs for this URL.

```php
public function redirectLogs(): HasMany
public function logs(): HasMany // Alias
```

**Example:**

```php
$url = Url::find(1);
$logs = $url->logs;

foreach ($logs as $log) {
    echo "IP: {$log->ip}, Browser: {$log->browser}\n";
}
```

### Methods

#### hasExpired()

Check if the URL has expired.

```php
public function hasExpired(): bool
```

**Example:**

```php
$url = Url::find(1);

if ($url->hasExpired()) {
    echo "This URL has expired";
}
```

#### markAsExpired()

Mark the URL as expired and dispatch event.

```php
public function markAsExpired(): void
```

**Example:**

```php
$url->markAsExpired();
```

#### checkHealth()

Check if the original URL is reachable.

```php
public function checkHealth(): bool
```

**Returns:** `bool` - True if URL is reachable

**Example:**

```php
$url = Url::find(1);

if ($url->checkHealth()) {
    echo "URL is healthy";
} else {
    echo "URL is unreachable";
}
```

### Scopes

#### active()

Get only active (non-expired) URLs.

```php
public function scopeActive($query)
```

**Example:**

```php
$activeUrls = Url::active()->get();
```

#### expired()

Get only expired URLs.

```php
public function scopeExpired($query)
```

**Example:**

```php
$expiredUrls = Url::expired()->get();
```

### Usage Examples

#### Creating URLs

```php
$url = Url::create([
    'user_id' => 1,
    'original_url' => 'https://example.com',
    'shortened_url' => 'abc123',
    'expires_at' => now()->addDays(7),
]);
```

#### Querying URLs

```php
// Find by slug
$url = Url::where('shortened_url', 'abc123')->first();

// Find by user
$userUrls = Url::where('user_id', 1)->get();

// Active URLs only
$active = Url::active()->get();

// Expiring soon
$expiringSoon = Url::whereBetween('expires_at', [
    now(),
    now()->addDays(7),
])->get();
```

#### Updating URLs

```php
$url = Url::find(1);

$url->update([
    'shortened_url' => 'new-slug',
    'expires_at' => now()->addMonths(1),
]);
```

#### With Analytics

```php
$url = Url::withCount('logs')->find(1);

echo "Total clicks: {$url->logs_count}";
```

## RedirectLog Model

Stores analytics data for each URL access.

### Namespace

```php
use CleaniqueCoders\Shrinkr\Models\RedirectLog;
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Primary key |
| `url_id` | int | Associated URL ID |
| `ip` | string | Visitor IP address |
| `browser` | string\|null | Browser name |
| `platform` | string\|null | Operating system |
| `device` | string\|null | Device type |
| `referrer` | string\|null | Referring URL |
| `headers` | array\|null | Request headers (JSON) |
| `query_parameters` | array\|null | Query params (JSON) |
| `created_at` | Carbon | Access timestamp |

### Fillable Attributes

```php
protected $fillable = [
    'url_id',
    'ip',
    'browser',
    'platform',
    'device',
    'referrer',
    'headers',
    'query_parameters',
];
```

### Casts

```php
protected $casts = [
    'headers' => 'array',
    'query_parameters' => 'array',
];
```

### Relationships

#### url()

Get the associated URL.

```php
public function url(): BelongsTo
```

**Example:**

```php
$log = RedirectLog::find(1);
$url = $log->url;

echo "Original URL: {$url->original_url}";
```

### Usage Examples

#### Querying Logs

```php
// Recent logs
$recentLogs = RedirectLog::latest()->take(100)->get();

// Logs for specific URL
$urlLogs = RedirectLog::where('url_id', 1)->get();

// Logs from today
$todayLogs = RedirectLog::whereDate('created_at', today())->get();

// By browser
$chromeUsers = RedirectLog::where('browser', 'Chrome')->get();
```

#### Analytics Queries

```php
// Browser statistics
$browserStats = RedirectLog::selectRaw('browser, COUNT(*) as count')
    ->groupBy('browser')
    ->orderBy('count', 'desc')
    ->get();

// Top referrers
$topReferrers = RedirectLog::whereNotNull('referrer')
    ->selectRaw('referrer, COUNT(*) as count')
    ->groupBy('referrer')
    ->orderBy('count', 'desc')
    ->take(10)
    ->get();

// Device breakdown
$deviceStats = RedirectLog::selectRaw('device, COUNT(*) as count')
    ->groupBy('device')
    ->get();
```

## Model Events

Both models support Laravel's model events:

```php
// In a service provider
Url::creating(function ($url) {
    $url->uuid = Str::orderedUuid();
});

Url::created(function ($url) {
    event(new UrlCreated($url));
});

Url::deleting(function ($url) {
    $url->logs()->delete();
});
```

## Extending Models

### Custom Url Model

```php
namespace App\Models;

use CleaniqueCoders\Shrinkr\Models\Url as BaseUrl;

class Url extends BaseUrl
{
    protected $appends = ['full_url', 'qr_code'];

    public function getFullUrlAttribute(): string
    {
        return config('app.url') . '/' . $this->shortened_url;
    }

    public function getQrCodeAttribute(): string
    {
        return "https://api.qrserver.com/v1/create-qr-code/?data="
            . urlencode($this->full_url);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

Configure in `config/shrinkr.php`:

```php
'models' => [
    'url' => \App\Models\Url::class,
],
```

## Factory Usage

### Url Factory

```php
use CleaniqueCoders\Shrinkr\Models\Url;

// Create a URL
$url = Url::factory()->create();

// With specific attributes
$url = Url::factory()->create([
    'user_id' => 1,
    'original_url' => 'https://example.com',
]);

// Create multiple
$urls = Url::factory()->count(10)->create();

// With logs
$url = Url::factory()->hasLogs(5)->create();
```

### RedirectLog Factory

```php
use CleaniqueCoders\Shrinkr\Models\RedirectLog;

// Create a log
$log = RedirectLog::factory()->create([
    'url_id' => 1,
]);

// With specific browser
$log = RedirectLog::factory()->create([
    'browser' => 'Chrome',
    'platform' => 'Windows',
]);
```

## Testing with Models

```php
use CleaniqueCoders\Shrinkr\Models\Url;
use CleaniqueCoders\Shrinkr\Models\RedirectLog;

test('url has many logs', function () {
    $url = Url::factory()->hasLogs(3)->create();

    expect($url->logs)->toHaveCount(3);
});

test('url can check if expired', function () {
    $url = Url::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    expect($url->hasExpired())->toBeTrue();
});

test('log belongs to url', function () {
    $url = Url::factory()->create();
    $log = RedirectLog::factory()->create(['url_id' => $url->id]);

    expect($log->url->id)->toBe($url->id);
});
```

## Next Steps

- [Events](04-events.md) - Events dispatched by models
- [Model Customization](../02-configuration/04-models.md) - Extend models
- [Usage Examples](../03-usage/README.md) - Practical model usage

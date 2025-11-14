# Logging Configuration

Shrinkr provides flexible logging options to track URL redirects and analytics.

## Available Loggers

### Log to File

Logs redirect events to a file.

```php
'logger' => \CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile::class,
```

**Log Location:** `storage/logs/laravel.log`

**Log Format:**

```
[2024-10-18 12:34:56] local.INFO: URL accessed: https://yourdomain.com/s/abc123
[2024-10-18 12:34:56] local.INFO: Redirect details: {"ip":"192.168.1.1","browser":"Chrome","platform":"Windows"}
```

### Log to Database

Logs redirect events to the `redirect_logs` table.

```php
'logger' => \CleaniqueCoders\Shrinkr\Actions\Logger\LogToDatabase::class,
```

**Database Table:** `redirect_logs`

**Stored Information:**

- `url_id` - The shortened URL ID
- `ip` - Visitor's IP address
- `browser` - Browser name (e.g., Chrome, Firefox)
- `platform` - Operating system (e.g., Windows, macOS, Linux)
- `device` - Device type (desktop, mobile, tablet)
- `referrer` - Referring website
- `headers` - Request headers (JSON)
- `query_parameters` - URL query parameters (JSON)
- `created_at` - Timestamp of the visit

## Custom Logger

You can create a custom logger by implementing the `Logger` contract.

### Step 1: Create Logger Class

```php
<?php

namespace App\Loggers;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;

class CustomLogger implements Logger
{
    public function log(Url $url, Request $request): void
    {
        // Your custom logging logic here
        // Example: Send to external service
        Http::post('https://analytics.example.com/track', [
            'url_id' => $url->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);
    }
}
```

### Step 2: Configure Custom Logger

Update `config/shrinkr.php`:

```php
'logger' => \App\Loggers\CustomLogger::class,
```

## Logger Comparison

| Feature | LogToFile | LogToDatabase | Custom |
|---------|-----------|---------------|--------|
| Easy Setup | ✅ | ✅ | ⚠️ |
| Queryable | ❌ | ✅ | Depends |
| Performance | Fast | Moderate | Depends |
| Storage | File system | Database | Depends |
| Analytics | Limited | Full | Custom |

## Querying Database Logs

When using `LogToDatabase`, you can query the logs:

### Get Recent Logs

```php
use CleaniqueCoders\Shrinkr\Models\RedirectLog;

$recentLogs = RedirectLog::latest()
    ->take(100)
    ->get();
```

### Get Logs for Specific URL

```php
$urlLogs = RedirectLog::where('url_id', $urlId)
    ->orderBy('created_at', 'desc')
    ->get();
```

### Analytics Queries

```php
// Most popular browsers
$browsers = RedirectLog::selectRaw('browser, COUNT(*) as count')
    ->groupBy('browser')
    ->orderBy('count', 'desc')
    ->get();

// Traffic by platform
$platforms = RedirectLog::selectRaw('platform, COUNT(*) as count')
    ->groupBy('platform')
    ->get();

// Top referrers
$referrers = RedirectLog::selectRaw('referrer, COUNT(*) as count')
    ->whereNotNull('referrer')
    ->groupBy('referrer')
    ->orderBy('count', 'desc')
    ->take(10)
    ->get();
```

## Performance Considerations

### File Logging

**Pros:**

- Minimal performance impact
- No database overhead
- Simple setup

**Cons:**

- Not queryable
- Limited analytics
- Log rotation needed

**Best for:** High-traffic sites where analytics aren't critical

### Database Logging

**Pros:**

- Full queryable data
- Rich analytics capabilities
- Structured data

**Cons:**

- Database write overhead
- Storage requirements
- Potential performance impact at scale

**Best for:** Sites needing detailed analytics and reporting

### Optimization Tips

**For Database Logging:**

1. **Add indexes** to frequently queried columns:

```php
Schema::table('redirect_logs', function (Blueprint $table) {
    $table->index('url_id');
    $table->index('created_at');
    $table->index(['url_id', 'created_at']);
});
```

2. **Archive old logs** periodically:

```php
// Archive logs older than 90 days
RedirectLog::where('created_at', '<', now()->subDays(90))->delete();
```

3. **Use queue jobs** for logging:

```php
class CustomLogger implements Logger
{
    public function log(Url $url, Request $request): void
    {
        dispatch(new LogRedirectJob($url, $request));
    }
}
```

## Next Steps

- [Routing Configuration](03-routing.md) - Configure routing and middleware
- [Model Customization](04-models.md) - Extend logging models
- [Analytics](../04-features/02-analytics.md) - Build analytics dashboards

# Contracts

Shrinkr provides contracts (interfaces) that define the structure for key components, allowing you to implement custom behavior.

## Logger Contract

The Logger interface defines how URL access events should be logged.

### Interface

```php
namespace CleaniqueCoders\Shrinkr\Contracts;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

interface Logger
{
    /**
     * Log URL access with request and agent details
     *
     * @param Url $url The accessed URL model
     * @param Request $request The HTTP request
     * @param Agent $agent The user agent parser
     * @return void
     */
    public function log(Url $url, Request $request, Agent $agent): void;
}
```

### Built-in Implementations

#### LogToDatabase

Logs URL access to the `redirect_logs` table.

```php
use CleaniqueCoders\Shrinkr\Actions\Logger\LogToDatabase;

$logger = new LogToDatabase();
$logger->log($url, $request, $agent);
```

**What it logs:**
- IP address
- Browser name and version
- Operating system (platform)
- Device type (desktop, mobile, tablet)
- Referrer URL
- Request headers (JSON)
- Query parameters (JSON)

#### LogToFile

Logs URL access to Laravel's log files.

```php
use CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile;

$logger = new LogToFile();
$logger->log($url, $request, $agent);
```

**Log format:**

```
[2024-01-15 10:30:45] local.INFO: URL accessed: abc123 (https://example.com)
```

### Custom Logger Implementation

Create a custom logger for specialized logging needs:

```php
namespace App\Services\Shrinkr;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Http;

class ElasticsearchLogger implements Logger
{
    public function log(Url $url, Request $request, Agent $agent): void
    {
        Http::post(config('services.elasticsearch.host') . '/url-logs/_doc', [
            'url_id' => $url->id,
            'slug' => $url->shortened_url,
            'original_url' => $url->original_url,
            'user_id' => $url->user_id,
            'ip' => $request->ip(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'device' => $agent->device(),
            'is_mobile' => $agent->isMobile(),
            'is_robot' => $agent->isRobot(),
            'referrer' => $request->header('referer'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

### Multi-Logger Implementation

Log to multiple destinations:

```php
namespace App\Services\Shrinkr;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class MultiLogger implements Logger
{
    public function __construct(
        protected array $loggers = []
    ) {}

    public function log(Url $url, Request $request, Agent $agent): void
    {
        foreach ($this->loggers as $logger) {
            $logger->log($url, $request, $agent);
        }
    }

    public function addLogger(Logger $logger): self
    {
        $this->loggers[] = $logger;
        return $this;
    }
}
```

Usage:

```php
use App\Services\Shrinkr\MultiLogger;
use CleaniqueCoders\Shrinkr\Actions\Logger\LogToDatabase;
use CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile;
use App\Services\Shrinkr\ElasticsearchLogger;

$logger = new MultiLogger([
    new LogToDatabase(),
    new LogToFile(),
    new ElasticsearchLogger(),
]);

$logger->log($url, $request, $agent);
```

### Conditional Logger

Log only under certain conditions:

```php
namespace App\Services\Shrinkr;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class ConditionalLogger implements Logger
{
    public function __construct(
        protected Logger $logger
    ) {}

    public function log(Url $url, Request $request, Agent $agent): void
    {
        // Don't log bot traffic
        if ($agent->isRobot()) {
            return;
        }

        // Don't log internal traffic
        if (str_starts_with($request->ip(), '192.168.')) {
            return;
        }

        $this->logger->log($url, $request, $agent);
    }
}
```

### Anonymous Logger

Log without storing personal data:

```php
namespace App\Services\Shrinkr;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use CleaniqueCoders\Shrinkr\Models\RedirectLog;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class AnonymousLogger implements Logger
{
    public function log(Url $url, Request $request, Agent $agent): void
    {
        RedirectLog::create([
            'url_id' => $url->id,
            'ip' => $this->anonymizeIp($request->ip()),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'device' => $agent->device(),
            // Don't store headers, query params, or referrer
        ]);
    }

    protected function anonymizeIp(string $ip): string
    {
        $parts = explode('.', $ip);
        $parts[3] = '0'; // Mask last octet
        return implode('.', $parts);
    }
}
```

### Configuring Custom Logger

Register in `AppServiceProvider`:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use CleaniqueCoders\Shrinkr\Contracts\Logger;
use App\Services\Shrinkr\ElasticsearchLogger;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Logger::class, ElasticsearchLogger::class);
    }
}
```

Or configure in `config/shrinkr.php`:

```php
return [
    'logger' => \App\Services\Shrinkr\ElasticsearchLogger::class,
];
```

## Testing Custom Implementations

### Test Custom Logger

```php
use App\Services\Shrinkr\ElasticsearchLogger;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;

test('elasticsearch logger sends data', function () {
    Http::fake();

    $url = Url::factory()->create();
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
    ]);
    $agent = new Agent();
    $agent->setUserAgent($request->userAgent());

    $logger = new ElasticsearchLogger();
    $logger->log($url, $request, $agent);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'url-logs/_doc');
    });
});
```

### Test Multi-Logger

```php
use App\Services\Shrinkr\MultiLogger;
use CleaniqueCoders\Shrinkr\Contracts\Logger;
use Mockery;

test('multi logger calls all loggers', function () {
    $logger1 = Mockery::mock(Logger::class);
    $logger2 = Mockery::mock(Logger::class);

    $logger1->shouldReceive('log')->once();
    $logger2->shouldReceive('log')->once();

    $multiLogger = new MultiLogger([$logger1, $logger2]);

    $url = Url::factory()->create();
    $request = Request::create('/');
    $agent = new Agent();

    $multiLogger->log($url, $request, $agent);
});
```

### Test Conditional Logger

```php
use App\Services\Shrinkr\ConditionalLogger;
use CleaniqueCoders\Shrinkr\Contracts\Logger;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Mockery;

test('conditional logger skips bots', function () {
    $innerLogger = Mockery::mock(Logger::class);
    $innerLogger->shouldNotReceive('log');

    $logger = new ConditionalLogger($innerLogger);

    $url = Url::factory()->create();
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Googlebot/2.1',
    ]);
    $agent = new Agent();
    $agent->setUserAgent($request->userAgent());

    $logger->log($url, $request, $agent);
});

test('conditional logger logs real users', function () {
    $innerLogger = Mockery::mock(Logger::class);
    $innerLogger->shouldReceive('log')->once();

    $logger = new ConditionalLogger($innerLogger);

    $url = Url::factory()->create();
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    ]);
    $agent = new Agent();
    $agent->setUserAgent($request->userAgent());

    $logger->log($url, $request, $agent);
});
```

## Advanced Patterns

### Queue-Based Logger

Process logs asynchronously:

```php
namespace App\Services\Shrinkr;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use App\Jobs\ProcessUrlLog;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class QueuedLogger implements Logger
{
    public function log(Url $url, Request $request, Agent $agent): void
    {
        ProcessUrlLog::dispatch($url->id, [
            'ip' => $request->ip(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'device' => $agent->device(),
            'referrer' => $request->header('referer'),
        ]);
    }
}
```

### Batched Logger

Batch log entries for performance:

```php
namespace App\Services\Shrinkr;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use CleaniqueCoders\Shrinkr\Models\RedirectLog;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class BatchedLogger implements Logger
{
    protected array $batch = [];

    public function log(Url $url, Request $request, Agent $agent): void
    {
        $this->batch[] = [
            'url_id' => $url->id,
            'ip' => $request->ip(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'device' => $agent->device(),
            'created_at' => now(),
        ];

        if (count($this->batch) >= 100) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if (!empty($this->batch)) {
            RedirectLog::insert($this->batch);
            $this->batch = [];
        }
    }
}
```

## Next Steps

- [Configuration: Logging](../02-configuration/02-logging.md) - Configure logging
- [Features: Analytics](../04-features/02-analytics.md) - Use logged data
- [Actions](02-actions.md) - Actions using Logger contract

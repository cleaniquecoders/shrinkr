# Commands

Shrinkr provides Artisan commands for maintenance and automation.

## Available Commands

### Check Expiry

Mark expired URLs as inactive.

```bash
php artisan shrinkr:check-expiry
```

**What it does:**

- Finds all URLs where `expires_at` is in the past
- Marks them as expired
- Dispatches `UrlExpired` event for each
- Outputs summary of expired URLs

**Example output:**

```
Checking for expired URLs...
Found 15 expired URLs.
URL abc123 has expired.
URL xyz456 has expired.
...
Expired URL check completed.
```

### Check Health

Validate that original URLs are still reachable.

```bash
php artisan shrinkr:check-health
```

**What it does:**

- Checks each URL's original destination
- Marks unreachable URLs as expired
- Dispatches `UrlExpired` event for failed checks
- Outputs health status for each URL

**Example output:**

```
Checking health for 100 URLs...
URL abc123 is active.
URL xyz456 is expired (unreachable).
URL def789 is active.
...
Health check completed: 95 active, 5 expired.
```

## Scheduling Commands

Add commands to Laravel's scheduler in `app/Console/Kernel.php`:

### Hourly Checks

```php
protected function schedule(Schedule $schedule): void
{
    // Check for expired URLs every hour
    $schedule->command('shrinkr:check-expiry')->hourly();

    // Check URL health every hour
    $schedule->command('shrinkr:check-health')->hourly();
}
```

### Daily Checks

```php
protected function schedule(Schedule $schedule): void
{
    // Check expiry daily at 2 AM
    $schedule->command('shrinkr:check-expiry')->dailyAt('02:00');

    // Check health daily at 3 AM
    $schedule->command('shrinkr:check-health')->dailyAt('03:00');
}
```

### Weekly Checks

```php
protected function schedule(Schedule $schedule): void
{
    // Check health weekly on Sunday at midnight
    $schedule->command('shrinkr:check-health')->weekly()->sundays()->at('00:00');
}
```

### With Notifications

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('shrinkr:check-expiry')
        ->hourly()
        ->onSuccess(function () {
            // Notify on success
        })
        ->onFailure(function () {
            // Notify on failure
        });
}
```

## Custom Commands

### Create Cleanup Command

```bash
php artisan make:command CleanupShrinkrUrls
```

```php
namespace App\Console\Commands;

use CleaniqueCoders\Shrinkr\Models\Url;
use CleaniqueCoders\Shrinkr\Models\RedirectLog;
use Illuminate\Console\Command;

class CleanupShrinkrUrls extends Command
{
    protected $signature = 'shrinkr:cleanup {--days=90 : Days to keep}';
    protected $description = 'Clean up old expired URLs and logs';

    public function handle(): int
    {
        $days = $this->option('days');

        $this->info("Cleaning up URLs and logs older than {$days} days...");

        // Delete old expired URLs
        $expiredCount = Url::whereNotNull('expires_at')
            ->where('expires_at', '<', now()->subDays($days))
            ->count();

        Url::whereNotNull('expires_at')
            ->where('expires_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$expiredCount} expired URLs");

        // Delete old logs
        $logCount = RedirectLog::where('created_at', '<', now()->subDays($days))
            ->count();

        RedirectLog::where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$logCount} old logs");

        $this->info('Cleanup completed successfully!');

        return self::SUCCESS;
    }
}
```

Schedule the cleanup:

```php
$schedule->command('shrinkr:cleanup --days=90')->weekly();
```

### Bulk URL Generation Command

```php
namespace App\Console\Commands;

use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use Illuminate\Console\Command;

class GenerateCampaignUrls extends Command
{
    protected $signature = 'shrinkr:generate-campaign {file : CSV file path}';
    protected $description = 'Generate shortened URLs from CSV file';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $csv = array_map('str_getcsv', file($file));
        $headers = array_shift($csv);

        $this->info("Processing " . count($csv) . " URLs...");

        $bar = $this->output->createProgressBar(count($csv));

        foreach ($csv as $row) {
            $data = array_combine($headers, $row);

            try {
                Shrinkr::shorten(
                    $data['url'],
                    $data['user_id'],
                    [
                        'custom_slug' => $data['slug'] ?? null,
                        'expiry_duration' => $data['expiry_minutes'] ?? null,
                    ]
                );

                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nFailed to create: {$data['url']} - {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('URL generation completed!');

        return self::SUCCESS;
    }
}
```

### Statistics Command

```php
namespace App\Console\Commands;

use CleaniqueCoders\Shrinkr\Models\Url;
use CleaniqueCoders\Shrinkr\Models\RedirectLog;
use Illuminate\Console\Command;

class ShrinkrStats extends Command
{
    protected $signature = 'shrinkr:stats';
    protected $description = 'Display Shrinkr statistics';

    public function handle(): int
    {
        $stats = [
            ['Metric', 'Value'],
            ['Total URLs', Url::count()],
            ['Active URLs', Url::whereNull('expires_at')->orWhere('expires_at', '>', now())->count()],
            ['Expired URLs', Url::whereNotNull('expires_at')->where('expires_at', '<=', now())->count()],
            ['Total Clicks', RedirectLog::count()],
            ['Clicks Today', RedirectLog::whereDate('created_at', today())->count()],
            ['Clicks This Week', RedirectLog::where('created_at', '>=', now()->startOfWeek())->count()],
            ['Clicks This Month', RedirectLog::whereMonth('created_at', now()->month)->count()],
        ];

        $this->table(['Metric', 'Value'], array_slice($stats, 1));

        // Top URLs
        $this->info("\nTop 10 URLs by clicks:");

        $topUrls = Url::withCount('logs')
            ->orderBy('logs_count', 'desc')
            ->take(10)
            ->get();

        $topUrlsTable = $topUrls->map(function ($url) {
            return [
                $url->slug,
                $url->logs_count,
            ];
        })->toArray();

        $this->table(['Slug', 'Clicks'], $topUrlsTable);

        return self::SUCCESS;
    }
}
```

## Running Commands

### Manually

```bash
php artisan shrinkr:check-expiry
php artisan shrinkr:check-health
php artisan shrinkr:cleanup
php artisan shrinkr:stats
```

### From Code

```php
use Illuminate\Support\Facades\Artisan;

// Run synchronously
Artisan::call('shrinkr:check-expiry');

// Run in background (queued)
Artisan::queue('shrinkr:check-health');

// With options
Artisan::call('shrinkr:cleanup', ['--days' => 30]);
```

### Via Scheduler

The scheduler runs automatically if you've added this to your cron:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Next Steps

- [Events](03-events.md) - Events dispatched by commands
- [Health Monitoring](04-health-monitoring.md) - URL health checks
- [URL Expiry](01-url-expiry.md) - Expiration management

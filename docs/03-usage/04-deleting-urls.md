# Deleting URLs

Learn how to delete shortened URLs and clean up associated data.

## Using the Facade

### Delete a URL

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use CleaniqueCoders\Shrinkr\Models\Url;

$url = Url::find(1);

Shrinkr::delete($url);
```

This will permanently delete the URL and its associated logs.

## Using the Action Class

```php
use CleaniqueCoders\Shrinkr\Actions\DeleteShortUrlAction;
use CleaniqueCoders\Shrinkr\Models\Url;

$url = Url::find(1);

$action = new DeleteShortUrlAction();
$action->execute($url);
```

## Direct Model Deletion

```php
$url = Url::find(1);
$url->delete();
```

## Soft Deletes

If you want to implement soft deletes, update your Url model:

```php
<?php

namespace App\Models;

use CleaniqueCoders\Shrinkr\Models\Url as BaseUrl;
use Illuminate\Database\Eloquent\SoftDeletes;

class Url extends BaseUrl
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
}
```

Add the migration:

```php
Schema::table('urls', function (Blueprint $table) {
    $table->softDeletes();
});
```

Now deletions will be soft:

```php
$url->delete(); // Soft delete
$url->forceDelete(); // Permanent delete
$url->restore(); // Restore soft deleted URL
```

## Cascading Deletes

Ensure related logs are deleted:

```php
// In your Url model
protected static function boot()
{
    parent::boot();

    static::deleting(function ($url) {
        // Delete all associated logs
        $url->logs()->delete();
    });
}
```

## Common Deletion Patterns

### Delete Expired URLs

```php
use CleaniqueCoders\Shrinkr\Models\Url;

// Delete all expired URLs
Url::whereNotNull('expires_at')
    ->where('expires_at', '<=', now())
    ->each(function ($url) {
        $url->delete();
    });
```

### Delete Old URLs

```php
// Delete URLs older than 90 days
Url::where('created_at', '<', now()->subDays(90))
    ->each(function ($url) {
        $url->delete();
    });
```

### Delete Unused URLs

```php
// Delete URLs with no visits
Url::doesntHave('logs')
    ->where('created_at', '<', now()->subDays(30))
    ->each(function ($url) {
        $url->delete();
    });
```

### Bulk Delete by User

```php
// Delete all URLs for a specific user
Url::where('user_id', $userId)->delete();
```

## Controller Example

```php
<?php

namespace App\Http\Controllers;

use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;

class UrlController extends Controller
{
    public function destroy(Url $url)
    {
        // Authorize
        $this->authorize('delete', $url);

        Shrinkr::delete($url);

        return response()->json([
            'success' => true,
            'message' => 'URL deleted successfully',
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'url_ids' => 'required|array',
            'url_ids.*' => 'exists:urls,id',
        ]);

        $urls = Url::whereIn('id', $validated['url_ids'])
            ->where('user_id', auth()->id())
            ->get();

        foreach ($urls as $url) {
            Shrinkr::delete($url);
        }

        return response()->json([
            'success' => true,
            'message' => count($urls) . ' URLs deleted successfully',
        ]);
    }
}
```

## Authorization

### Policy Example

```php
<?php

namespace App\Policies;

use App\Models\User;
use CleaniqueCoders\Shrinkr\Models\Url;

class UrlPolicy
{
    public function delete(User $user, Url $url): bool
    {
        return $user->id === $url->user_id;
    }

    public function forceDelete(User $user, Url $url): bool
    {
        return $user->isAdmin();
    }
}
```

Register the policy:

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    Url::class => UrlPolicy::class,
];
```

Use in controller:

```php
public function destroy(Url $url)
{
    $this->authorize('delete', $url);

    Shrinkr::delete($url);

    return redirect()->back()->with('success', 'URL deleted');
}
```

## Scheduled Cleanup

### Artisan Command

```php
<?php

namespace App\Console\Commands;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Console\Command;

class CleanupExpiredUrls extends Command
{
    protected $signature = 'urls:cleanup {--days=90}';
    protected $description = 'Clean up expired and old URLs';

    public function handle(): int
    {
        $days = $this->option('days');

        // Delete expired URLs
        $expiredCount = Url::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->count();

        Url::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        $this->info("Deleted {$expiredCount} expired URLs");

        // Delete old URLs
        $oldCount = Url::where('created_at', '<', now()->subDays($days))
            ->doesntHave('logs')
            ->count();

        Url::where('created_at', '<', now()->subDays($days))
            ->doesntHave('logs')
            ->delete();

        $this->info("Deleted {$oldCount} old unused URLs");

        return self::SUCCESS;
    }
}
```

### Schedule the Command

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Run daily cleanup
    $schedule->command('urls:cleanup')->daily();

    // Run weekly with custom retention
    $schedule->command('urls:cleanup --days=180')->weekly();
}
```

## Deleting Logs Only

Keep the URL but delete its logs:

```php
$url = Url::find(1);
$url->logs()->delete();
```

Delete old logs:

```php
use CleaniqueCoders\Shrinkr\Models\RedirectLog;

// Delete logs older than 30 days
RedirectLog::where('created_at', '<', now()->subDays(30))->delete();
```

## API Example

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/urls/{url}', [ApiUrlController::class, 'destroy']);
    Route::post('/urls/bulk-delete', [ApiUrlController::class, 'bulkDestroy']);
});

// app/Http/Controllers/Api/ApiUrlController.php
class ApiUrlController extends Controller
{
    public function destroy(Url $url)
    {
        $this->authorize('delete', $url);

        Shrinkr::delete($url);

        return response()->json([
            'message' => 'URL deleted successfully',
        ], 200);
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer|exists:urls,id',
        ]);

        $deleted = Url::whereIn('id', $validated['ids'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'message' => "{$deleted} URLs deleted successfully",
            'count' => $deleted,
        ]);
    }
}
```

## Testing Deletions

```php
// tests/Feature/UrlDeletionTest.php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use CleaniqueCoders\Shrinkr\Models\Url;

test('can delete url', function () {
    $url = Url::factory()->create();

    Shrinkr::delete($url);

    expect(Url::find($url->id))->toBeNull();
});

test('deletes associated logs when url is deleted', function () {
    $url = Url::factory()->hasLogs(5)->create();

    $url->delete();

    expect($url->logs()->count())->toBe(0);
});

test('user can only delete own urls', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $url = Url::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->delete(route('urls.destroy', $url))
        ->assertForbidden();
});
```

## Next Steps

- [Working with Users](05-working-with-users.md) - User-specific URL management
- [Analytics](../04-features/02-analytics.md) - Analyze before deletion
- [Commands](../04-features/06-commands.md) - Automated cleanup commands

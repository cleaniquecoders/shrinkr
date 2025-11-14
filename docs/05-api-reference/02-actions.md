# Actions

Action classes provide the core business logic for URL operations in Shrinkr.

## Overview

Actions follow the single-responsibility principle, with each action handling one specific operation. They can be used directly or through the Shrinkr facade.

## Available Actions

### CreateShortUrlAction

Creates a new shortened URL.

**Namespace:**

```php
use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;
```

**Method:**

```php
public function execute(array $data): Url
```

**Parameters:**

- `$data` (array):
  - `original_url` (string, required) - The URL to shorten
  - `user_id` (int, required) - The user creating the URL
  - `custom_slug` (string, optional) - Custom slug
  - `expiry_duration` (int, optional) - Expiry in minutes
  - `uuid` (string, optional) - Custom UUID

**Returns:** `Url` - The created URL model

**Throws:** `SlugAlreadyExistsException`

**Example:**

```php
$action = new CreateShortUrlAction();

$url = $action->execute([
    'original_url' => 'https://example.com',
    'user_id' => 1,
    'custom_slug' => 'my-link',
    'expiry_duration' => 60,
]);
```

### UpdateShortUrlAction

Updates an existing shortened URL.

**Namespace:**

```php
use CleaniqueCoders\Shrinkr\Actions\UpdateShortUrlAction;
```

**Method:**

```php
public function execute(Url $url, array $data): Url
```

**Parameters:**

- `$url` (Url) - The URL model to update
- `$data` (array):
  - `custom_slug` (string, optional) - New slug
  - `expiry_duration` (int, optional) - New expiry in minutes

**Returns:** `Url` - The updated URL model

**Throws:** `SlugAlreadyExistsException`

**Example:**

```php
$action = new UpdateShortUrlAction();

$url = Url::find(1);
$updated = $action->execute($url, [
    'custom_slug' => 'updated-slug',
    'expiry_duration' => 120,
]);
```

### DeleteShortUrlAction

Deletes a shortened URL.

**Namespace:**

```php
use CleaniqueCoders\Shrinkr\Actions\DeleteShortUrlAction;
```

**Method:**

```php
public function execute(Url $url): bool
```

**Parameters:**

- `$url` (Url) - The URL model to delete

**Returns:** `bool` - True if successful

**Example:**

```php
$action = new DeleteShortUrlAction();

$url = Url::find(1);
$action->execute($url);
```

### CheckUrlHealthAction

Checks if the original URL is reachable.

**Namespace:**

```php
use CleaniqueCoders\Shrinkr\Actions\CheckUrlHealthAction;
```

**Method:**

```php
public function execute(Url $url): bool
```

**Parameters:**

- `$url` (Url) - The URL model to check

**Returns:** `bool` - True if URL is healthy, false otherwise

**Side Effects:**
- Updates `is_expired` status
- Dispatches `UrlExpired` event if URL is unreachable

**Example:**

```php
$action = new CheckUrlHealthAction();

$url = Url::find(1);
$isHealthy = $action->execute($url);

if ($isHealthy) {
    echo "URL is reachable";
} else {
    echo "URL is down or expired";
}
```

## Logger Actions

### LogToFile

Logs redirect events to Laravel log files.

**Namespace:**

```php
use CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile;
```

**Method:**

```php
public function log(Url $url, Request $request): void
```

**Example:**

```php
$logger = new LogToFile();
$logger->log($url, $request);
```

### LogToDatabase

Logs redirect events to the database.

**Namespace:**

```php
use CleaniqueCoders\Shrinkr\Actions\Logger\LogToDatabase;
```

**Method:**

```php
public function log(Url $url, Request $request): void
```

**Example:**

```php
$logger = new LogToDatabase();
$logger->log($url, $request);
```

**Data Logged:**
- IP address
- Browser name and version
- Platform (OS)
- Device type
- Referrer
- Request headers (JSON)
- Query parameters (JSON)

## Using Actions Directly

### In Controllers

```php
namespace App\Http\Controllers;

use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;
use CleaniqueCoders\Shrinkr\Actions\UpdateShortUrlAction;
use CleaniqueCoders\Shrinkr\Actions\DeleteShortUrlAction;
use Illuminate\Http\Request;

class UrlController extends Controller
{
    public function __construct(
        private CreateShortUrlAction $createAction,
        private UpdateShortUrlAction $updateAction,
        private DeleteShortUrlAction $deleteAction
    ) {}

    public function store(Request $request)
    {
        $url = $this->createAction->execute([
            'original_url' => $request->url,
            'user_id' => auth()->id(),
            'custom_slug' => $request->slug,
        ]);

        return response()->json($url);
    }

    public function update(Request $request, Url $url)
    {
        $updated = $this->updateAction->execute($url, [
            'custom_slug' => $request->slug,
        ]);

        return response()->json($updated);
    }

    public function destroy(Url $url)
    {
        $this->deleteAction->execute($url);

        return response()->noContent();
    }
}
```

### In Service Classes

```php
namespace App\Services;

use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;
use CleaniqueCoders\Shrinkr\Models\Url;

class UrlService
{
    public function __construct(
        private CreateShortUrlAction $createAction
    ) {}

    public function createBulkUrls(array $urls, int $userId): array
    {
        return collect($urls)->map(function ($urlData) use ($userId) {
            return $this->createAction->execute([
                'original_url' => $urlData['url'],
                'user_id' => $userId,
                'custom_slug' => $urlData['slug'] ?? null,
            ]);
        })->toArray();
    }
}
```

### In Commands

```php
namespace App\Console\Commands;

use CleaniqueCoders\Shrinkr\Actions\CheckUrlHealthAction;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Console\Command;

class CheckUrlsHealth extends Command
{
    protected $signature = 'urls:check';

    public function handle(CheckUrlHealthAction $action)
    {
        $urls = Url::all();

        $bar = $this->output->createProgressBar($urls->count());

        foreach ($urls as $url) {
            $isHealthy = $action->execute($url);

            $this->info(
                $isHealthy
                    ? "✓ {$url->slug}"
                    : "✗ {$url->slug}"
            );

            $bar->advance();
        }

        $bar->finish();
    }
}
```

## Creating Custom Actions

Extend or create new actions for custom behavior:

```php
namespace App\Actions;

use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;
use CleaniqueCoders\Shrinkr\Models\Url;

class CreateTrackedUrlAction extends CreateShortUrlAction
{
    public function execute(array $data): Url
    {
        // Add custom tracking
        $data['metadata'] = [
            'source' => request()->header('Referer'),
            'campaign' => request()->query('utm_campaign'),
        ];

        $url = parent::execute($data);

        // Send to analytics
        Analytics::trackUrlCreation($url);

        return $url;
    }
}
```

## Testing Actions

```php
use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;
use CleaniqueCoders\Shrinkr\Models\Url;

test('create action creates url', function () {
    $action = new CreateShortUrlAction();

    $url = $action->execute([
        'original_url' => 'https://example.com',
        'user_id' => 1,
    ]);

    expect($url)->toBeInstanceOf(Url::class)
        ->and($url->original_url)->toBe('https://example.com');
});

test('create action throws exception for duplicate slug', function () {
    Url::factory()->create(['shortened_url' => 'existing']);

    $action = new CreateShortUrlAction();

    $action->execute([
        'original_url' => 'https://example.com',
        'user_id' => 1,
        'custom_slug' => 'existing',
    ]);
})->throws(\CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException::class);
```

## Next Steps

- [Models](03-models.md) - Url and RedirectLog models
- [Events](04-events.md) - Events dispatched by actions
- [Exceptions](05-exceptions.md) - Exception handling
